<?php

namespace App\Services\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PromotionRedemption;
use App\Services\Costs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Único lugar donde un pedido pasa a "pagado". Es seguro llamarlo varias veces. */
class OrderPayments
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    /**
     * Consulta al procesador y, si el checkout está pagado, marca el pedido.
     * Si el cliente inició una transferencia que todavía no llega, marca el pago "en proceso".
     * Devuelve true si el pedido quedó pagado (ahora o antes).
     */
    public function syncFromGateway(Payment $payment): bool
    {
        if ($payment->status === PaymentStatus::Succeeded) {
            return true;
        }

        $remote = $this->gateway->fetchCheckout($payment);

        if ($remote->paid) {
            return $this->markPaid($payment, $remote);
        }

        if ($remote->processing) {
            $this->markProcessing($payment, $remote);
        }

        return false;
    }

    /** La transferencia está en camino: el pedido sigue esperando pago, pero no se abre otro cobro. */
    public function markProcessing(Payment $payment, RemoteCheckout $remote): void
    {
        Payment::whereKey($payment->id)
            ->where('status', PaymentStatus::Pending->value)
            ->update([
                'status' => PaymentStatus::Processing->value,
                'method' => $remote->paymentMethod,
                'updated_at' => now(),
            ]);

        $payment->refresh();
    }

    public function markPaid(Payment $payment, RemoteCheckout $remote): bool
    {
        return DB::transaction(function () use ($payment, $remote) {
            /** @var Payment $payment */
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            /** @var Order $order */
            $order = Order::whereKey($payment->order_id)->lockForUpdate()->firstOrFail();

            if ($payment->status === PaymentStatus::Succeeded) {
                return true;
            }

            if ($remote->totalCents === null || $remote->totalCents !== $order->total_cents) {
                // Se cobró otro monto (o el procesador no lo informó): no se confirma solo;
                // alguien lo tiene que revisar.
                Log::critical('Monto pagado distinto al del pedido', [
                    'order' => $order->reference,
                    'expected' => $order->total_cents,
                    'paid' => $remote->totalCents,
                ]);

                return false;
            }

            // El total con transferencia trae descuento. Si el cliente terminó pagando con tarjeta,
            // el cobro es válido pero Twist & Rolls absorbe la comisión: queda registrado para revisarlo.
            if ($order->payment_method === PaymentMethod::BankTransfer
                && $remote->paymentMethod !== null
                && $remote->paymentMethod !== PaymentMethod::BankTransfer->value) {
                Log::warning('Pedido con precio de transferencia pagado con otro método', [
                    'order' => $order->reference,
                    'method' => $remote->paymentMethod,
                ]);
            }

            $payment->forceFill([
                'status' => PaymentStatus::Succeeded,
                'method' => $remote->paymentMethod ?? $payment->method,
                'provider_payment_id' => $remote->paymentId ?? $payment->provider_payment_id,
                'card_brand' => $remote->cardBrand,
                'card_last4' => $remote->cardLast4,
                'paid_at' => now(),
            ])->save();

            if (! $order->isPaid()) {
                if ($order->status !== OrderStatus::PendingPayment) {
                    Log::warning('Llegó un pago para un pedido que no esperaba pago', [
                        'order' => $order->reference,
                        'status' => $order->status->value,
                    ]);
                }

                $order->forceFill([
                    'status' => OrderStatus::Paid,
                    // Comisión según el método con el que realmente se pagó.
                    'payment_fee_cents' => Costs::paymentFee($remote->paymentMethod ?? $order->payment_method, $order->total_cents),
                    'paid_at' => now(),
                    'last_contact_at' => now(),
                    'closed_at' => null,
                ])->save();

                if ($order->promotion_id) {
                    PromotionRedemption::firstOrCreate(
                        ['order_id' => $order->id],
                        [
                            'promotion_id' => $order->promotion_id,
                            'customer_phone' => $order->customer_phone,
                            'discount_cents' => $order->discount_cents,
                        ],
                    );
                }
            }

            return true;
        });
    }
}

<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;

/**
 * Procesador simulado para desarrollo local: muestra una página propia
 * con botones que confirman el pago sin tarjeta ni transferencia real.
 */
class FakeGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'fake';
    }

    public function createCheckout(Order $order, Payment $payment, string $successUrl, string $cancelUrl): CheckoutSession
    {
        $ttl = $order->payment_method === PaymentMethod::BankTransfer
            ? (int) config('rolobox.checkout.transfer_ttl_minutes')
            : (int) config('rolobox.checkout.payment_ttl_minutes');

        return new CheckoutSession(
            id: 'fake_'.Str::lower(Str::random(20)),
            url: route('fake-payment.show', $payment),
            expiresAt: now()->addMinutes($ttl),
        );
    }

    public function fetchCheckout(Payment $payment): RemoteCheckout
    {
        $byTransfer = $payment->order->payment_method === PaymentMethod::BankTransfer;

        return new RemoteCheckout(
            id: (string) $payment->provider_checkout_id,
            paid: $payment->status === PaymentStatus::Succeeded,
            totalCents: $payment->amount_cents,
            paymentId: $payment->provider_payment_id,
            cardBrand: $byTransfer ? null : 'Visa',
            cardLast4: $byTransfer ? null : '4242',
            paymentMethod: $payment->order->payment_method->value,
            processing: $payment->status === PaymentStatus::Processing,
        );
    }
}

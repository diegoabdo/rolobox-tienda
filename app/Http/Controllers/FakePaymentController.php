<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Payments\OrderPayments;
use App\Services\Payments\RemoteCheckout;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Solo existe con PAYMENTS_DRIVER=fake fuera de producción (ver routes/web.php). */
class FakePaymentController extends Controller
{
    public function show(Payment $payment): Response
    {
        $order = $payment->order;

        return Inertia::render('PagoPrueba', [
            'paymentId' => $payment->id,
            'reference' => $order->reference,
            'totalCents' => $payment->amount_cents,
            'paid' => $payment->status === PaymentStatus::Succeeded,
            'method' => $order->payment_method->value,
        ]);
    }

    public function confirm(Payment $payment, OrderPayments $payments): RedirectResponse
    {
        $payments->markPaid($payment, new RemoteCheckout(
            id: (string) $payment->provider_checkout_id,
            paid: true,
            totalCents: $payment->amount_cents,
            paymentId: 'fake_pay_'.$payment->id,
            cardBrand: $payment->order->payment_method === PaymentMethod::Card ? 'Visa' : null,
            cardLast4: $payment->order->payment_method === PaymentMethod::Card ? '4242' : null,
            paymentMethod: $payment->order->payment_method->value,
        ));

        $order = $payment->order;

        return redirect()->route('payments.return', ['order' => $order->reference, 'token' => $order->public_token]);
    }

    /** Simula que el cliente inició una transferencia que todavía no se acredita. */
    public function pending(Payment $payment, OrderPayments $payments): RedirectResponse
    {
        $payments->markProcessing($payment, new RemoteCheckout(
            id: (string) $payment->provider_checkout_id,
            paid: false,
            paymentMethod: PaymentMethod::BankTransfer->value,
            processing: true,
        ));

        $order = $payment->order;

        return redirect()->route('payments.return', ['order' => $order->reference, 'token' => $order->public_token]);
    }

    public function cancel(Payment $payment): RedirectResponse
    {
        $order = $payment->order;

        return redirect()->route('payments.cancelled', ['order' => $order->reference, 'token' => $order->public_token]);
    }
}

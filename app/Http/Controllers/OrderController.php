<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Checkout;
use App\Services\Payments\OrderPayments;
use App\Services\Payments\PaymentGatewayException;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class OrderController extends Controller
{
    public function show(Request $request, Order $order, OrderPayments $payments): Response
    {
        abort_unless($order->hasValidToken($request->query('token')), 404);

        // Si el pago todavía no se confirmó (el webhook puede tardar), se consulta al procesador,
        // como máximo una vez cada 10 segundos por pedido.
        if ($order->status === OrderStatus::PendingPayment) {
            $payment = $order->payments()->latest('id')->first();
            if (in_array($payment?->status, [PaymentStatus::Pending, PaymentStatus::Processing], true) && $payment->provider_checkout_id
                && Cache::add("order-sync:{$order->id}", true, 10)) {
                try {
                    $payments->syncFromGateway($payment);
                    $order->refresh();
                } catch (PaymentGatewayException) {
                    // Se reintenta en la próxima carga; el webhook también lo confirma.
                }
            }
        }

        $order->load('items', 'payments');
        $view = $order->toCustomerView();

        return Inertia::render('Pedido', [
            'order' => $view,
            'token' => $order->public_token,
            'canRetryPayment' => $order->status === OrderStatus::PendingPayment && ! $view['transferInProgress'],
        ]);
    }

    public function pay(Request $request, Order $order, Checkout $checkout): SymfonyResponse
    {
        abort_unless($order->hasValidToken($request->input('token')), 404);

        return Inertia::location($checkout->resume($order));
    }

    public function lookupForm(): Response
    {
        return Inertia::render('Seguir');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:30'],
        ], [], ['reference' => 'código de pedido', 'phone' => 'teléfono']);

        $reference = strtoupper(trim($data['reference']));
        if (! str_starts_with($reference, 'RB-')) {
            $reference = 'RB-'.$reference;
        }

        $order = Order::where('reference', $reference)->first();
        $phone = Phone::normalize($data['phone']);

        // Mismo mensaje si no existe el pedido o no coincide el teléfono: no se revela cuál falló.
        if (! $order || ! $phone || ! hash_equals($order->customer_phone, $phone)) {
            throw ValidationException::withMessages([
                'reference' => 'No encontramos un pedido con ese código y ese teléfono.',
            ]);
        }

        return redirect()->route('orders.show', ['order' => $order->reference, 'token' => $order->public_token]);
    }
}

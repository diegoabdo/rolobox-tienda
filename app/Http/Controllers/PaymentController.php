<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Payments\OrderPayments;
use App\Services\Payments\PaymentGatewayException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Páginas a las que vuelve el cliente desde el procesador de pagos. */
class PaymentController extends Controller
{
    public function return(Request $request, Order $order, OrderPayments $payments): RedirectResponse
    {
        abort_unless($order->hasValidToken($request->query('token')), 404);

        // Volver a esta URL no prueba que se pagó: se confirma consultando al procesador.
        $payment = $order->payments()->latest('id')->first();
        if ($payment) {
            try {
                $payments->syncFromGateway($payment);
            } catch (PaymentGatewayException) {
                // La página del pedido vuelve a consultar y el webhook también confirma.
            }
        }

        return redirect()->route('orders.show', ['order' => $order->reference, 'token' => $order->public_token]);
    }

    public function cancelled(Request $request, Order $order): Response|RedirectResponse
    {
        abort_unless($order->hasValidToken($request->query('token')), 404);

        if ($order->isPaid()) {
            return redirect()->route('orders.show', ['order' => $order->reference, 'token' => $order->public_token]);
        }

        return Inertia::render('PagoCancelado', [
            'reference' => $order->reference,
            'totalCents' => $order->total_cents,
            'token' => $order->public_token,
        ]);
    }
}

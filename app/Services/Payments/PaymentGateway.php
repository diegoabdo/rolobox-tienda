<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGateway
{
    /** Nombre corto que se guarda en payments.provider. */
    public function name(): string;

    /**
     * Crea la página de pago hospedada por el procesador.
     *
     * @throws PaymentGatewayException
     */
    public function createCheckout(Order $order, Payment $payment, string $successUrl, string $cancelUrl): CheckoutSession;

    /**
     * Consulta al procesador el estado real de un checkout. Es la fuente de verdad:
     * un pedido solo se marca pagado con lo que responde aquí.
     *
     * @throws PaymentGatewayException
     */
    public function fetchCheckout(Payment $payment): RemoteCheckout;
}

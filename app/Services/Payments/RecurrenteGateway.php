<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Integración con la página de pago hospedada de Recurrente.
 * Docs: https://docs.recurrente.com
 */
class RecurrenteGateway implements PaymentGateway
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $secretKey,
    ) {}

    public function name(): string
    {
        return 'recurrente';
    }

    public function createCheckout(Order $order, Payment $payment, string $successUrl, string $cancelUrl): CheckoutSession
    {
        $byTransfer = $order->payment_method === PaymentMethod::BankTransfer;
        $expiresAt = now()->addMinutes((int) config($byTransfer
            ? 'rolobox.checkout.transfer_ttl_minutes'
            : 'rolobox.checkout.payment_ttl_minutes'));

        // Cada producto del pedido va como su propio concepto, para que el cliente vea en Recurrente
        // qué está comprando. Los montos ya traen el descuento y el envío calculados por Twist & Rolls,
        // así que suman exactamente el total del pedido.
        $response = $this->send(fn (PendingRequest $http) => $http->post('/checkouts', [
            'items' => $this->items($order),
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => $order->reference,
            'expires_at' => $expiresAt->toIso8601String(),
            // La referencia de la transferencia la genera Recurrente: si se reintenta el pago del mismo
            // pedido, una referencia fija chocaría con la del intento anterior.
        ]));

        $id = $response->json('id');
        $url = $response->json('checkout_url');

        if (! is_string($id) || ! is_string($url)) {
            throw new PaymentGatewayException('Recurrente no devolvió un checkout válido.');
        }

        return new CheckoutSession($id, $url, $expiresAt);
    }

    /**
     * Los conceptos del cobro: uno por producto del pedido (con sus sabores) y uno por el envío.
     * El descuento se reparte entre los productos en proporción a su monto. Si por cualquier razón
     * los montos no suman el total del pedido, se cobra un solo concepto con el total.
     *
     * @return list<array<string, mixed>>
     */
    private function items(Order $order): array
    {
        $order->loadMissing('items');

        $lines = $order->items->map(fn ($item): array => [
            'name' => $this->itemName($item),
            'amount' => (int) $item->line_total_cents,
        ])->values()->all();

        $discount = (int) $order->discount_cents;
        $subtotal = array_sum(array_column($lines, 'amount'));
        if ($discount > 0 && $subtotal > 0) {
            $left = $discount;
            foreach ($lines as $i => $line) {
                $share = $i === array_key_last($lines) ? $left : intdiv($discount * $line['amount'], $subtotal);
                $lines[$i]['amount'] -= $share;
                $left -= $share;
            }
        }

        if ((int) $order->shipping_cents > 0) {
            $lines[] = ['name' => 'Envío', 'amount' => (int) $order->shipping_cents];
        }

        $valid = $lines !== []
            && array_sum(array_column($lines, 'amount')) === (int) $order->total_cents
            && min(array_column($lines, 'amount')) >= 1;

        if (! $valid) {
            $lines = [['name' => "Pedido {$order->reference} · Twist & Rolls", 'amount' => (int) $order->total_cents]];
        }

        return array_map(fn (array $line): array => [
            'name' => $line['name'],
            'amount_in_cents' => $line['amount'],
            'currency' => 'GTQ',
            'quantity' => 1,
            'charge_type' => 'one_time',
            'available_installments' => [],
            // Solo el método que eligió el cliente: el total ya trae su precio
            // (con transferencia hay descuento). Si aun así paga con otro método,
            // OrderPayments lo registra en el log para revisarlo.
            'payment_method_types' => [$order->payment_method->value],
        ], $lines);
    }

    /** «2 × Caja de seis roles: 3 canela clásica, 3 chocolate». */
    private function itemName(OrderItem $item): string
    {
        $flavors = collect($item->flavors ?? [])
            ->map(fn (array $flavor): string => ((int) ($flavor['count'] ?? 1) > 1 ? $flavor['count'].' ' : '').Str::lower((string) ($flavor['name'] ?? '')))
            ->filter()
            ->implode(', ');

        $name = ($item->quantity > 1 ? "{$item->quantity} × " : '').$item->product_name.($flavors !== '' ? ": {$flavors}" : '');

        return Str::limit($name, 100);
    }

    public function fetchCheckout(Payment $payment): RemoteCheckout
    {
        if (! $payment->provider_checkout_id) {
            throw new PaymentGatewayException('El pago no tiene checkout en Recurrente.');
        }

        $data = $this->send(fn (PendingRequest $http) => $http->get('/checkouts/'.rawurlencode($payment->provider_checkout_id)))->json();

        $last4 = data_get($data, 'payment_method.card.last4') ?? data_get($data, 'payment_method.last4');
        $method = data_get($data, 'payment_method.type');
        $brand = data_get($data, 'payment_method.card.network') ?? data_get($data, 'payment_method.card.brand');

        return new RemoteCheckout(
            id: (string) data_get($data, 'id', $payment->provider_checkout_id),
            paid: data_get($data, 'status') === 'paid',
            totalCents: is_numeric(data_get($data, 'total_in_cents')) ? (int) data_get($data, 'total_in_cents') : null,
            paymentId: data_get($data, 'payment.id'),
            cardBrand: is_string($brand) ? ucfirst($brand) : null,
            cardLast4: is_string($last4) && preg_match('/^\d{4}$/', $last4) ? $last4 : null,
            paymentMethod: is_string($method) ? $method : null,
            processing: data_get($data, 'status') === 'payment_in_progress',
        );
    }

    /** @param  callable(PendingRequest): Response  $call */
    private function send(callable $call): Response
    {
        if (! $this->secretKey) {
            throw new PaymentGatewayException('Falta RECURRENTE_SECRET_KEY.');
        }

        try {
            $response = $call(
                Http::baseUrl(rtrim($this->baseUrl, '/'))
                    ->withHeaders(['X-SECRET-KEY' => $this->secretKey])
                    ->acceptJson()
                    ->asJson()
                    ->timeout(15)
                    ->retry(2, 300, fn (\Throwable $e) => $e instanceof ConnectionException, throw: false)
            );
        } catch (ConnectionException $e) {
            throw new PaymentGatewayException('No se pudo conectar con Recurrente.', previous: $e);
        }

        if ($response->failed()) {
            // Se registra el código y el mensaje de error, nunca datos del cliente.
            Log::warning('Recurrente respondió con error', [
                'status' => $response->status(),
                'error' => $response->json('error') ?? $response->json('message'),
            ]);
            throw new PaymentGatewayException('Recurrente rechazó la solicitud ('.$response->status().').');
        }

        return $response;
    }
}

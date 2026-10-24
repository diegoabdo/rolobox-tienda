<?php

namespace App\Services;

use App\Enums\DeliveryType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Zone;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentGatewayException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class Checkout
{
    public function __construct(
        private readonly Cart $cart,
        private readonly Pricing $pricing,
        private readonly PaymentGateway $gateway,
    ) {}

    /**
     * Crea el pedido y su link de pago. Devuelve la URL a la que hay que mandar al cliente.
     *
     * @param  array<string, mixed>  $data  Datos ya validados por CheckoutRequest.
     */
    public function place(array $data): string
    {
        // Mismo envío repetido (doble clic, reintento): se reutiliza el pedido existente.
        $existing = Order::where('idempotency_key', $data['idempotency_key'])->first();
        if ($existing) {
            return $this->reuse($existing, $data);
        }

        $cart = $this->cart->resolve();
        if ($cart['lines'] === []) {
            throw ValidationException::withMessages(['cart' => 'Tu carrito está vacío.']);
        }
        if ($cart['removed'] > 0) {
            throw ValidationException::withMessages([
                'cart' => 'Algunos productos ya no están disponibles y los quitamos del carrito. Revisa tu pedido.',
            ]);
        }

        /** @var Zone $zone */
        $zone = Zone::active()->findOrFail($data['zone_id']);
        if ($zone->delivery_type === DeliveryType::National && ! $cart['nationalReady']) {
            throw ValidationException::withMessages([
                'zone_id' => 'Algunos sabores de tu carrito no se envían por paquetería. Elige reparto local o cambia los sabores.',
            ]);
        }

        $method = PaymentMethod::from($data['payment_method']);
        $quote = $this->pricing->quote($cart, $zone, $data['promotion_code'] ?? null, $data['phone'], $method);
        if ($quote['promotionError']) {
            throw ValidationException::withMessages(['promotion_code' => $quote['promotionError']]);
        }
        if ($quote['totalCents'] < 500) {
            throw ValidationException::withMessages(['cart' => 'El total mínimo para pagar con tarjeta es Q5.00.']);
        }

        try {
            $order = $this->createOrder($data, $cart, $zone, $quote);
        } catch (UniqueConstraintViolationException $e) {
            // Dos envíos simultáneos con la misma llave: el otro ya creó el pedido.
            $existing = Order::where('idempotency_key', $data['idempotency_key'])->first();
            if (! $existing) {
                throw $e;
            }

            return $this->reuse($existing, $data);
        }

        $url = $this->startPayment($order);

        // El carrito se vacía solo cuando ya hay link de pago. Si falla, el cliente reintenta
        // con el mismo formulario y se reutiliza este mismo pedido (misma llave de idempotencia).
        $this->cart->clear();

        return $url;
    }

    /** Mismo envío repetido (doble clic, reintento): se reutiliza el pedido existente. */
    private function reuse(Order $existing, array $data): string
    {
        if (! hash_equals($existing->customer_phone, (string) $data['phone'])) {
            throw ValidationException::withMessages(['cart' => 'Recarga la página e intenta de nuevo.']);
        }

        $url = $this->resume($existing);
        $this->cart->clear();

        return $url;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $cart
     * @param  array<string, mixed>  $quote
     */
    private function createOrder(array $data, array $cart, Zone $zone, array $quote): Order
    {
        // Costos al momento de la venta: quedan guardados en el pedido para los reportes.
        $products = Product::whereIn('id', array_column($cart['lines'], 'productId'))->get()->keyBy('id');
        $flavorCosts = Costs::flavorCosts(array_merge(...array_map(
            fn (array $line) => array_column($line['flavors'], 'id'),
            $cart['lines'],
        )));
        $lineCosts = [];
        foreach ($cart['lines'] as $i => $line) {
            $product = $products->get($line['productId']);
            $lineCosts[$i] = $product ? Costs::line($product, $line['flavors'], $line['quantity'], $flavorCosts) : 0;
        }

        return DB::transaction(function () use ($data, $cart, $zone, $quote, $lineCosts) {
            $customer = Customer::updateOrCreate(
                ['phone' => $data['phone']],
                ['name' => $data['name'], 'email' => $data['email'] ?? null],
            );

            $isLocal = $zone->delivery_type === DeliveryType::Local;

            $order = Order::create([
                'idempotency_key' => $data['idempotency_key'],
                'customer_id' => $customer->id,
                'customer_name' => $data['name'],
                'customer_phone' => $data['phone'],
                'customer_email' => $data['email'] ?? null,
                'nit' => $data['nit'] ?? 'CF',
                'delivery_type' => $zone->delivery_type,
                'zone_id' => $zone->id,
                'zone_name' => $zone->label(),
                'address' => $data['address'],
                'address_reference' => $data['address_reference'] ?? null,
                'delivery_date' => $isLocal ? $data['delivery_date'] : null,
                'delivery_slot' => $isLocal ? $data['delivery_slot'] : null,
                'notes' => $data['notes'] ?? null,
                'promotion_id' => $quote['promotion']?->id,
                'promotion_code' => $quote['promotion']?->code,
                'subtotal_cents' => $quote['subtotalCents'],
                'discount_cents' => $quote['discountCents'],
                'shipping_cents' => $quote['shippingCents'],
                'total_cents' => $quote['totalCents'],
                'cost_cents' => array_sum($lineCosts),
                'delivery_cost_cents' => (int) $zone->delivery_cost_cents,
                'status' => OrderStatus::PendingPayment,
                'payment_method' => $quote['paymentMethod'],
                'legal_version' => config('rolobox.legal_version'),
                'accepted_at' => now(),
            ]);

            $byTransfer = $quote['paymentMethod'] === PaymentMethod::BankTransfer;

            foreach ($cart['lines'] as $i => $line) {
                $order->items()->create([
                    'product_id' => $line['productId'],
                    'product_name' => $line['name'],
                    'product_type' => $line['type'],
                    'flavors' => $line['flavors'],
                    'quantity' => $line['quantity'],
                    'unit_price_cents' => $byTransfer ? $line['transferUnitPriceCents'] : $line['unitPriceCents'],
                    'line_total_cents' => $byTransfer ? $line['transferLineTotalCents'] : $line['lineTotalCents'],
                    'cost_cents' => $lineCosts[$i],
                    'gift_message' => $line['giftMessage'],
                ]);
            }

            return $order;
        });
    }

    /** Vuelve a mandar al cliente a pagar un pedido existente (o a verlo, si ya está pagado). */
    public function resume(Order $order): string
    {
        if ($order->isPaid()) {
            return route('orders.show', ['order' => $order->reference, 'token' => $order->public_token]);
        }

        if ($order->status !== OrderStatus::PendingPayment) {
            throw ValidationException::withMessages(['cart' => 'Este pedido ya no se puede pagar. Arma uno nuevo.']);
        }

        $payment = $order->payments()->latest('id')->first();

        // Hay una transferencia en camino: no se abre otro cobro, se muestra el pedido.
        if ($payment?->status === PaymentStatus::Processing) {
            return route('orders.show', ['order' => $order->reference, 'token' => $order->public_token]);
        }

        if ($payment && $payment->isReusable()) {
            return $payment->checkout_url;
        }

        return $this->startPayment($order);
    }

    private function startPayment(Order $order): string
    {
        $payment = $order->payments()->create([
            'provider' => $this->gateway->name(),
            'status' => PaymentStatus::Pending,
            'amount_cents' => $order->total_cents,
        ]);

        $params = ['order' => $order->reference, 'token' => $order->public_token];

        try {
            $session = $this->gateway->createCheckout(
                $order,
                $payment,
                successUrl: route('payments.return', $params),
                cancelUrl: route('payments.cancelled', $params),
            );
        } catch (PaymentGatewayException $e) {
            $payment->update(['status' => PaymentStatus::Failed]);
            Log::error('No se pudo crear el checkout', ['order' => $order->reference, 'error' => $e->getMessage()]);

            throw ValidationException::withMessages([
                'payment' => 'No pudimos abrir la página de pago. Tu pedido quedó guardado: prueba de nuevo en unos minutos.',
            ]);
        }

        $payment->update([
            'provider_checkout_id' => $session->id,
            'checkout_url' => $session->url,
            'expires_at' => $session->expiresAt,
        ]);

        return $session->url;
    }
}

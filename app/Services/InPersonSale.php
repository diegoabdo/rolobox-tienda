<?php

namespace App\Services;

use App\Enums\DeliveryType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Enums\SalesChannel;
use App\Models\CashClosing;
use App\Models\Customer;
use App\Models\Flavor;
use App\Models\Order;
use App\Models\Product;
use App\Support\Phone;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Registra una venta hecha en persona (efectivo, POS o transferencia directa) como un pedido ya
 * pagado y entregado, para que cuente en los reportes, el cierre de caja y la ficha del cliente.
 */
class InPersonSale
{
    /**
     * @param  array{
     *     sold_on: string,
     *     payment_method: string,
     *     customer_id?: int|string|null,
     *     customer_name?: ?string,
     *     customer_phone?: ?string,
     *     items: list<array{product_id: int|string, quantity: int|string, flavor_id?: int|string|null, flavors?: list<array{flavor_id: int|string, count: int|string}>}>,
     *     discount_cents?: int|null,
     *     notes?: ?string,
     * }  $data
     */
    public function record(array $data, ?int $userId = null): Order
    {
        $tz = config('rolobox.timezone');
        $today = CarbonImmutable::now($tz)->startOfDay();
        $soldOn = CarbonImmutable::parse($data['sold_on'], $tz)->startOfDay();

        if ($soldOn->greaterThan($today)) {
            throw ValidationException::withMessages(['sold_on' => 'La fecha de la venta no puede ser futura.']);
        }
        if (CashClosing::covers($soldOn->toDateString())) {
            throw ValidationException::withMessages(['sold_on' => 'Ese día ya tiene cierre de caja. Borra el cierre para agregar ventas.']);
        }

        $method = PaymentMethod::from($data['payment_method']);
        $lines = $this->lines($data['items'] ?? [], $method);
        if ($lines === []) {
            throw ValidationException::withMessages(['items' => 'Agrega al menos un producto.']);
        }

        $subtotal = array_sum(array_column($lines, 'line_total_cents'));
        $discount = min(max(0, (int) ($data['discount_cents'] ?? 0)), $subtotal);
        $total = $subtotal - $discount;
        $customer = $this->customer($data);

        // Si se registra el mismo día, se usa la hora actual; si es un día anterior, el mediodía de ese día.
        $paidAt = $soldOn->isSameDay($today) ? now() : $soldOn->setTime(12, 0)->utc();

        return DB::transaction(function () use ($data, $userId, $method, $lines, $subtotal, $discount, $total, $customer, $soldOn, $paidAt) {
            $order = Order::create([
                'idempotency_key' => (string) Str::uuid(),
                'customer_id' => $customer?->id,
                'customer_name' => $customer?->name ?? 'Cliente en persona',
                'customer_phone' => $customer?->phone ?? '',
                'customer_email' => $customer?->email,
                'nit' => 'CF',
                'delivery_type' => DeliveryType::Local,
                'zone_id' => null,
                'zone_name' => 'Venta en persona',
                'address' => '',
                'delivery_date' => $soldOn->toDateString(),
                'notes' => $data['notes'] ?? null,
                'subtotal_cents' => $subtotal,
                'discount_cents' => $discount,
                'shipping_cents' => 0,
                'total_cents' => $total,
                'cost_cents' => array_sum(array_column($lines, 'cost_cents')),
                'delivery_cost_cents' => 0,
                'payment_fee_cents' => Costs::paymentFee($method, $total, inPerson: true),
                'status' => OrderStatus::Delivered,
                'channel' => SalesChannel::InPerson,
                'payment_method' => $method,
                'paid_at' => $paidAt,
                'legal_version' => 'venta-en-persona',
                'accepted_at' => $paidAt,
                'closed_at' => now(),
            ]);

            foreach ($lines as $line) {
                $order->items()->create($line);
            }

            $order->payments()->create([
                'provider' => 'in_person',
                'status' => PaymentStatus::Succeeded,
                'method' => $method->value,
                'amount_cents' => $total,
                'paid_at' => $paidAt,
            ]);

            return $order;
        });
    }

    /**
     * Arma las líneas del pedido con precios, sabores y costos actuales.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function lines(array $items, PaymentMethod $method): array
    {
        $products = Product::whereIn('id', array_map(fn ($i) => (int) ($i['product_id'] ?? 0), $items))->get()->keyBy('id');
        $flavors = Flavor::all()->keyBy('id');
        $flavorCosts = $flavors->map(fn (Flavor $f) => (int) $f->cost_cents)->all();

        $lines = [];
        foreach ($items as $i => $item) {
            $product = $products->get((int) ($item['product_id'] ?? 0));
            $quantity = (int) ($item['quantity'] ?? 0);
            if (! $product || $quantity < 1) {
                throw ValidationException::withMessages(["items.{$i}" => 'Revisa el producto y la cantidad de cada línea.']);
            }

            // Sabores: un rol suelto lleva uno; una caja, los que sumen su tamaño.
            $chosen = [];
            if ($product->type === ProductType::Single) {
                $chosen[(int) ($item['flavor_id'] ?? 0)] = 1;
            } else {
                foreach ($item['flavors'] ?? [] as $row) {
                    $id = (int) ($row['flavor_id'] ?? 0);
                    $chosen[$id] = ($chosen[$id] ?? 0) + (int) ($row['count'] ?? 0);
                }
            }

            $lineFlavors = [];
            foreach ($chosen as $id => $count) {
                $flavor = $flavors->get($id);
                if (! $flavor || $count < 1) {
                    throw ValidationException::withMessages(["items.{$i}" => "Elige los sabores de «{$product->name}»."]);
                }
                $lineFlavors[] = ['id' => $flavor->id, 'name' => $flavor->name, 'count' => $count];
            }

            $rolls = array_sum(array_column($lineFlavors, 'count'));
            if ($product->isBox() && $rolls !== (int) $product->box_size) {
                throw ValidationException::withMessages(["items.{$i}" => "La «{$product->name}» lleva {$product->box_size} roles y elegiste {$rolls}."]);
            }
            if (! $product->isBox() && $rolls !== 1) {
                throw ValidationException::withMessages(["items.{$i}" => "Elige el sabor del «{$product->name}»."]);
            }

            $unit = $product->priceFor($method);
            $lines[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_type' => $product->type->value,
                'flavors' => $lineFlavors,
                'quantity' => $quantity,
                'unit_price_cents' => $unit,
                'line_total_cents' => $unit * $quantity,
                'cost_cents' => Costs::line($product, $lineFlavors, $quantity, $flavorCosts),
                'gift_message' => null,
            ];
        }

        return $lines;
    }

    /** Cliente existente, uno nuevo si se dio teléfono, o ninguno. */
    private function customer(array $data): ?Customer
    {
        if (! empty($data['customer_id'])) {
            return Customer::find((int) $data['customer_id']);
        }

        $name = trim((string) ($data['customer_name'] ?? ''));
        $phone = Phone::normalize($data['customer_phone'] ?? null);

        if ($phone === null) {
            if (! empty($data['customer_phone'])) {
                throw ValidationException::withMessages(['customer_phone' => 'Escribe un número de Guatemala de 8 dígitos.']);
            }

            return null;
        }

        $customer = Customer::firstOrNew(['phone' => $phone]);
        if ($name !== '' || ! $customer->exists) {
            $customer->name = $name !== '' ? $name : 'Cliente';
        }
        $customer->save();

        return $customer;
    }
}

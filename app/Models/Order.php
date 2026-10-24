<?php

namespace App\Models;

use App\Enums\DeliveryType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SalesChannel;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'reference', 'public_token', 'idempotency_key',
        'customer_id', 'customer_name', 'customer_phone', 'customer_email', 'nit',
        'delivery_type', 'zone_id', 'zone_name', 'address', 'address_reference',
        'delivery_date', 'delivery_slot', 'notes',
        'promotion_id', 'promotion_code', 'subtotal_cents', 'discount_cents', 'shipping_cents', 'total_cents',
        'cost_cents', 'delivery_cost_cents', 'payment_fee_cents',
        'status', 'channel', 'payment_method', 'paid_at', 'legal_version', 'accepted_at',
        'record_key', 'last_contact_at', 'closed_at', 'retention_hold',
    ];

    protected $hidden = ['public_token', 'idempotency_key', 'record_key'];

    protected function casts(): array
    {
        return [
            'delivery_type' => DeliveryType::class,
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'channel' => SalesChannel::class,
            'delivery_date' => 'date',
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'shipping_cents' => 'integer',
            'total_cents' => 'integer',
            'cost_cents' => 'integer',
            'delivery_cost_cents' => 'integer',
            'payment_fee_cents' => 'integer',
            'paid_at' => 'datetime',
            'accepted_at' => 'datetime',
            'last_contact_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->reference ??= static::newReference();
            $order->public_token ??= Str::random(48);
            $order->record_key ??= bin2hex(random_bytes(16));
            $order->last_contact_at ??= now();
        });
    }

    /** Referencia corta y fácil de dictar por teléfono: RB- y 6 caracteres sin letras confusas. */
    public static function newReference(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $reference = 'RB-'.$code;
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function hasValidToken(?string $token): bool
    {
        return is_string($token) && $token !== '' && hash_equals($this->public_token, $token);
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    /**
     * Ganancia del pedido: lo cobrado menos el costo de los productos, la comisión del procesador
     * y lo que cuesta entregarlo. null si el pedido no tiene costos registrados.
     */
    public function profitCents(): ?int
    {
        if ($this->cost_cents === null) {
            return null;
        }

        return $this->total_cents - $this->cost_cents - (int) $this->payment_fee_cents - (int) $this->delivery_cost_cents;
    }

    /**
     * Estados a los que se puede pasar desde el actual (los botones del panel salen de aquí).
     *
     * @return list<OrderStatus>
     */
    public function allowedTransitions(): array
    {
        $dispatch = $this->delivery_type === DeliveryType::National
            ? OrderStatus::Shipped
            : OrderStatus::OutForDelivery;

        return match ($this->status) {
            OrderStatus::PendingPayment => [OrderStatus::Cancelled],
            OrderStatus::Paid => [OrderStatus::Baking, $dispatch, OrderStatus::Cancelled],
            OrderStatus::Baking => [$dispatch, OrderStatus::Cancelled],
            OrderStatus::OutForDelivery, OrderStatus::Shipped => [OrderStatus::Delivered],
            default => [],
        };
    }

    public function canMoveTo(OrderStatus $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /** Cambia el estado y deja registrado cuándo se cerró (para los plazos de conservación). */
    public function moveTo(OrderStatus $status): void
    {
        if (! $this->canMoveTo($status)) {
            throw new DomainException("El pedido {$this->reference} no puede pasar de «{$this->status->label()}» a «{$status->label()}».");
        }

        $this->forceFill([
            'status' => $status,
            'last_contact_at' => now(),
            'closed_at' => $status->isClosed() ? now() : null,
        ])->save();
    }

    /** Pasos que ve el cliente en la página de su pedido. */
    public function timeline(): array
    {
        $deliveryStep = $this->delivery_type === DeliveryType::National
            ? OrderStatus::Shipped
            : OrderStatus::OutForDelivery;

        $steps = [OrderStatus::Paid, OrderStatus::Baking, $deliveryStep, OrderStatus::Delivered];
        $order = array_map(fn (OrderStatus $s) => $s->value, $steps);
        $currentIndex = array_search($this->status->value, $order, true);

        return array_map(fn (OrderStatus $step, int $i) => [
            'key' => $step->value,
            'label' => $step->label(),
            'done' => $currentIndex !== false && $i < $currentIndex,
            'current' => $currentIndex !== false && $i === $currentIndex,
        ], $steps, array_keys($steps));
    }

    /** @return array<string, mixed> */
    public function toCustomerView(): array
    {
        $tz = config('rolobox.timezone');
        $payment = $this->relationLoaded('payments')
            ? $this->payments->sortByDesc('id')->first()
            : $this->payments()->latest('id')->first();

        return [
            'reference' => $this->reference,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'isPaid' => $this->isPaid(),
            'deliveryType' => $this->delivery_type->value,
            'deliveryLabel' => $this->delivery_type->label(),
            'zone' => $this->zone_name,
            'deliveryDate' => $this->delivery_date?->toDateString(),
            'deliverySlot' => $this->delivery_slot
                ? (config('rolobox.delivery.slots')[$this->delivery_slot] ?? $this->delivery_slot)
                : null,
            'customerName' => $this->customer_name,
            'items' => $this->items->map(fn (OrderItem $item) => [
                'name' => $item->product_name,
                'quantity' => $item->quantity,
                'flavors' => $item->flavors,
                'lineTotalCents' => $item->line_total_cents,
                'giftMessage' => $item->gift_message,
            ])->values(),
            'subtotalCents' => $this->subtotal_cents,
            'discountCents' => $this->discount_cents,
            'shippingCents' => $this->shipping_cents,
            'totalCents' => $this->total_cents,
            'promotionCode' => $this->promotion_code,
            'paymentMethod' => $this->payment_method->value,
            'paymentMethodLabel' => $this->payment_method->label(),
            'transferInProgress' => ! $this->isPaid() && $payment?->status === PaymentStatus::Processing,
            'paidAt' => $this->paid_at?->timezone($tz)->toIso8601String(),
            'card' => $payment && $payment->card_last4
                ? trim(($payment->card_brand ?? 'Tarjeta').' terminada en '.$payment->card_last4)
                : null,
            'timeline' => $this->timeline(),
        ];
    }
}

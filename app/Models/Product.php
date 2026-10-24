<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'type', 'description', 'box_size', 'price_cents', 'transfer_price_cents', 'packaging_cost_cents',
        'image_path', 'published', 'available', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'box_size' => 'integer',
            'price_cents' => 'integer',
            'transfer_price_cents' => 'integer',
            'packaging_cost_cents' => 'integer',
            'published' => 'boolean',
            'available' => 'boolean',
        ];
    }

    public function scopeForSale(Builder $query): Builder
    {
        return $query->where('published', true)->where('available', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true)->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Precio según cómo paga el cliente. El de tarjeta es el precio publicado; con transferencia
     * o en efectivo (sin comisión) aplica el precio con descuento.
     */
    public function priceFor(PaymentMethod $method): int
    {
        if ($method !== PaymentMethod::Card && $this->transfer_price_cents !== null) {
            return min($this->transfer_price_cents, $this->price_cents);
        }

        return $this->price_cents;
    }

    public function isBox(): bool
    {
        return $this->type === ProductType::Box;
    }

    /** @return array<string, mixed> */
    public function toStorefront(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type->value,
            'description' => $this->description,
            'boxSize' => $this->box_size,
            'priceCents' => $this->price_cents,
            'transferPriceCents' => $this->priceFor(PaymentMethod::BankTransfer),
            'image' => $this->image_path ? asset($this->image_path) : null,
            'available' => $this->available,
        ];
    }
}

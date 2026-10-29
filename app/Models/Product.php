<?php

namespace App\Models;

use App\Enums\BoxScope;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'slug', 'type', 'description', 'box_size', 'box_scope', 'price_cents', 'transfer_price_cents', 'packaging_cost_cents',
        'image_path', 'published', 'available', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'box_scope' => BoxScope::class,
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function isMixedBox(): bool
    {
        return $this->isBox() && $this->box_scope === BoxScope::Mixed;
    }

    /**
     * ¿Este sabor se puede pedir con este producto? Un producto suelto acepta los sabores de su categoría;
     * una caja, los de su categoría marcados para cajas, o los marcados para cajas mixtas si es mixta.
     */
    public function accepts(Flavor $flavor): bool
    {
        if ($this->isMixedBox()) {
            return $flavor->mix_eligible;
        }

        if ($flavor->category_id === null || $flavor->category_id !== $this->category_id) {
            return false;
        }

        return ! $this->isBox() || $flavor->box_eligible;
    }

    /** Sabores activos que se pueden pedir con este producto, en el orden del menú. */
    public function acceptedFlavors(): Builder
    {
        $query = Flavor::active();

        if ($this->isMixedBox()) {
            return $query->where('mix_eligible', true);
        }

        $query->where('category_id', $this->category_id);

        return $this->isBox() ? $query->where('box_eligible', true) : $query;
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
            'categoryId' => $this->category_id,
            'mixed' => $this->isMixedBox(),
            'priceCents' => $this->price_cents,
            'transferPriceCents' => $this->priceFor(PaymentMethod::BankTransfer),
            'image' => $this->image_path ? asset($this->image_path) : null,
            'available' => $this->available,
        ];
    }
}

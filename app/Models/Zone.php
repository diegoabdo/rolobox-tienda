<?php

namespace App\Models;

use App\Enums\DeliveryType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $fillable = [
        'name', 'department', 'municipality', 'delivery_type',
        'fee_cents', 'delivery_cost_cents', 'notes', 'active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'delivery_type' => DeliveryType::class,
            'fee_cents' => 'integer',
            'delivery_cost_cents' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function label(): string
    {
        return $this->name.', '.$this->municipality.', '.$this->department;
    }

    /** @return array<string, mixed> */
    public function toStorefront(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'department' => $this->department,
            'municipality' => $this->municipality,
            'deliveryType' => $this->delivery_type->value,
            'feeCents' => $this->fee_cents,
            'notes' => $this->notes,
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Flavor extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'allergens', 'image_path',
        'ships_nationally', 'cost_cents', 'active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'ships_nationally' => 'boolean',
            'cost_cents' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset($this->image_path) : null;
    }

    /** @return array<string, mixed> */
    public function toStorefront(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'allergens' => $this->allergens,
            'image' => $this->imageUrl(),
            'shipsNationally' => $this->ships_nationally,
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Flavor extends Model
{
    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'allergens', 'image_path',
        'ships_nationally', 'box_eligible', 'mix_eligible', 'cost_cents', 'extra_cents', 'active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'ships_nationally' => 'boolean',
            'box_eligible' => 'boolean',
            'mix_eligible' => 'boolean',
            'cost_cents' => 'integer',
            'extra_cents' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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
            'categoryId' => $this->category_id,
            'extraCents' => $this->extra_cents,
            'boxEligible' => $this->box_eligible,
            'mixEligible' => $this->mix_eligible,
        ];
    }
}

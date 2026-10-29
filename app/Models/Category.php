<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Una línea de la panadería: roles de canela, croissants, focaccias, trenzas… */
class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'image_path', 'active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function flavors(): HasMany
    {
        return $this->hasMany(Flavor::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
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
            'image' => $this->imageUrl(),
        ];
    }
}

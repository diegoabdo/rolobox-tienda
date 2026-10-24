<?php

namespace App\Models;

use App\Enums\PromotionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    protected $fillable = [
        'code', 'description', 'type', 'value', 'min_subtotal_cents', 'starts_at', 'ends_at',
        'max_redemptions', 'first_order_only', 'once_per_customer', 'applies_to',
        'allow_national', 'show_banner', 'banner_title', 'active',
    ];

    protected function casts(): array
    {
        return [
            'type' => PromotionType::class,
            'value' => 'integer',
            'min_subtotal_cents' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'max_redemptions' => 'integer',
            'first_order_only' => 'boolean',
            'once_per_customer' => 'boolean',
            'allow_national' => 'boolean',
            'show_banner' => 'boolean',
            'active' => 'boolean',
        ];
    }

    protected function code(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn (string $value) => mb_strtoupper(trim($value)),
        );
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(PromotionRedemption::class);
    }

    /** Promociones vigentes en este momento (sin revisar límites de uso). */
    public function scopeRunning(Builder $query): Builder
    {
        $now = now();

        return $query->where('active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $now));
    }

    /** @return array<string, mixed> */
    public function toBanner(): array
    {
        return [
            'code' => $this->code,
            'title' => $this->banner_title ?: $this->description,
            'description' => $this->description,
            'endsAt' => $this->ends_at?->timezone(config('rolobox.timezone'))->toDateString(),
            'firstOrderOnly' => $this->first_order_only,
        ];
    }
}

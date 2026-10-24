<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 'provider', 'provider_checkout_id', 'provider_payment_id', 'checkout_url',
        'status', 'method', 'amount_cents', 'card_brand', 'card_last4', 'expires_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount_cents' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isReusable(): bool
    {
        return $this->status === PaymentStatus::Pending
            && $this->checkout_url
            && $this->expires_at
            && $this->expires_at->isAfter(now()->addMinutes(5));
    }
}

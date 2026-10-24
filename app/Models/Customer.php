<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'notes'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** Pedidos que sí se pagaron (los que cuentan como compras). */
    public function paidOrders(): HasMany
    {
        return $this->orders()->whereNotNull('paid_at');
    }
}

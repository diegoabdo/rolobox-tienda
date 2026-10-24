<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Gasto del negocio: ingredientes, empaques, gas, transporte… Se resta en la ganancia neta. */
class Expense extends Model
{
    protected $fillable = [
        'spent_on', 'category', 'description', 'amount_cents', 'payment_method', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'spent_on' => 'date',
            'category' => ExpenseCategory::class,
            'payment_method' => PaymentMethod::class,
            'amount_cents' => 'integer',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Un gasto de un día ya cerrado no se puede cambiar: movería los números del cierre. */
    public function isLocked(): bool
    {
        return $this->spent_on !== null && CashClosing::covers($this->spent_on->toDateString());
    }
}

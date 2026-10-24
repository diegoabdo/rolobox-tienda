<?php

namespace App\Models;

use App\Services\SalesReport;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cierre de caja diario: guarda los números del día tal como estaban al cerrarlo, más lo que se
 * contó en efectivo y lo que llegó a la cuenta. Si después cambia algo, el cierre no cambia, y los
 * gastos y ventas en persona de un día cerrado ya no se pueden modificar.
 */
class CashClosing extends Model
{
    protected $fillable = [
        'period_start', 'period_end',
        'orders_count', 'gross_cents', 'card_cents', 'transfer_cents', 'cash_sales_cents', 'in_person_cents',
        'fees_cents', 'expected_deposit_cents', 'cost_cents', 'delivery_cost_cents', 'profit_cents',
        'expenses_cents', 'cash_expenses_cents', 'net_profit_cents',
        'opening_cash_cents', 'expected_cash_cents', 'counted_cash_cents', 'cash_difference_cents',
        'counted_cents', 'difference_cents', 'notes', 'closed_by',
    ];

    protected function casts(): array
    {
        $casts = ['period_start' => 'date', 'period_end' => 'date'];
        foreach ($this->fillable as $field) {
            if (str_ends_with($field, '_cents') || $field === 'orders_count') {
                $casts[$field] = 'integer';
            }
        }

        return $casts;
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Arma los datos de un cierre a partir de las ventas y gastos del periodo.
     *
     * @return array<string, mixed>
     */
    public static function snapshot(
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?int $countedDepositCents = null,
        int $openingCashCents = 0,
        ?int $countedCashCents = null,
    ): array {
        $t = (new SalesReport($from, $to))->totals();
        $expectedCash = $openingCashCents + $t['cash'] - $t['cashExpenses'];

        return [
            'period_start' => $from->toDateString(),
            'period_end' => $to->toDateString(),
            'orders_count' => $t['orders'],
            'gross_cents' => $t['gross'],
            'card_cents' => $t['card'],
            'transfer_cents' => $t['transfer'],
            'cash_sales_cents' => $t['cash'],
            'in_person_cents' => $t['inPerson'],
            'fees_cents' => $t['fees'],
            'expected_deposit_cents' => $t['expectedDeposit'],
            'cost_cents' => $t['cost'],
            'delivery_cost_cents' => $t['deliveryCost'],
            'profit_cents' => $t['profit'],
            'expenses_cents' => $t['expenses'],
            'cash_expenses_cents' => $t['cashExpenses'],
            'net_profit_cents' => $t['netProfit'],
            'opening_cash_cents' => $openingCashCents,
            'expected_cash_cents' => $expectedCash,
            'counted_cash_cents' => $countedCashCents,
            'cash_difference_cents' => $countedCashCents === null ? null : $countedCashCents - $expectedCash,
            'counted_cents' => $countedDepositCents,
            'difference_cents' => $countedDepositCents === null ? null : $countedDepositCents - $t['expectedDeposit'],
        ];
    }

    /** ¿La fecha (AAAA-MM-DD) ya está dentro de un cierre? */
    public static function covers(string $date): bool
    {
        return static::query()
            ->whereDate('period_start', '<=', $date)
            ->whereDate('period_end', '>=', $date)
            ->exists();
    }

    /** Día siguiente al último cierre, sin pasar de hoy (o hoy si todavía no hay cierres). */
    public static function nextPeriodStart(): CarbonImmutable
    {
        $tz = config('rolobox.timezone');
        $today = CarbonImmutable::now($tz)->startOfDay();
        $last = static::query()->latest('period_end')->value('period_end');

        if (! $last) {
            return $today;
        }

        $next = CarbonImmutable::parse($last, $tz)->addDay()->startOfDay();

        return $next->greaterThan($today) ? $today : $next;
    }

    /** El efectivo contado en el último cierre es el fondo con que abre el día siguiente. */
    public static function lastCountedCash(): int
    {
        return (int) static::query()->latest('period_end')->value('counted_cash_cents');
    }
}

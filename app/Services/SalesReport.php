<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\SalesChannel;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Números de ventas y gastos de un periodo (ventas por fecha de pago, en hora de Guatemala).
 *
 * Cuenta los pedidos pagados que no se cancelaron, en línea y en persona. Los pagados y luego
 * cancelados se informan aparte para revisarlos, porque ese dinero sí entró (o hubo que devolverlo).
 *
 * Ganancia = cobrado − comisiones − costo de productos − costo de entregas.
 * Ganancia neta = ganancia − gastos del negocio.
 */
class SalesReport
{
    /** @var Collection<int, Order> */
    private Collection $orders;

    /** @var Collection<int, Order> */
    private Collection $cancelled;

    /** @var Collection<int, Expense> */
    private Collection $expenses;

    public function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
    ) {
        $tz = config('rolobox.timezone');
        $start = $from->setTimezone($tz)->startOfDay()->utc();
        $end = $to->setTimezone($tz)->endOfDay()->utc();

        $paid = Order::query()
            ->with('items')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$start, $end])
            ->orderBy('paid_at')
            ->get();

        $this->orders = $paid->filter(fn (Order $o) => $o->status !== OrderStatus::Cancelled)->values();
        $this->cancelled = $paid->filter(fn (Order $o) => $o->status === OrderStatus::Cancelled)->values();

        $this->expenses = Expense::query()
            ->whereDate('spent_on', '>=', $from->setTimezone($tz)->toDateString())
            ->whereDate('spent_on', '<=', $to->setTimezone($tz)->toDateString())
            ->orderBy('spent_on')
            ->get();
    }

    public static function month(int $year, int $month): self
    {
        $first = CarbonImmutable::create($year, $month, 1, 0, 0, 0, config('rolobox.timezone'));

        return new self($first, $first->endOfMonth());
    }

    /** @return Collection<int, Order> */
    public function orders(): Collection
    {
        return $this->orders;
    }

    /** @return Collection<int, Expense> */
    public function expenses(): Collection
    {
        return $this->expenses;
    }

    /** @return array<string, int|float|null> */
    public function totals(): array
    {
        $gross = (int) $this->orders->sum('total_cents');
        $fees = (int) $this->orders->sum('payment_fee_cents');
        $cost = (int) $this->orders->sum('cost_cents');
        $delivery = (int) $this->orders->sum('delivery_cost_cents');
        $profit = $gross - $fees - $cost - $delivery;
        $count = $this->orders->count();
        $paidWith = fn (PaymentMethod $m) => (int) $this->orders->filter(fn (Order $o) => $o->payment_method === $m)->sum('total_cents');
        $card = $paidWith(PaymentMethod::Card);
        $transfer = $paidWith(PaymentMethod::BankTransfer);
        $cash = $paidWith(PaymentMethod::Cash);
        $expenses = (int) $this->expenses->sum('amount_cents');
        $cashExpenses = (int) $this->expenses->filter(fn (Expense $e) => $e->payment_method === PaymentMethod::Cash)->sum('amount_cents');
        $netProfit = $profit - $expenses;

        return [
            'orders' => $count,
            'gross' => $gross,
            'subtotal' => (int) $this->orders->sum('subtotal_cents'),
            'discounts' => (int) $this->orders->sum('discount_cents'),
            'shipping' => (int) $this->orders->sum('shipping_cents'),
            'card' => $card,
            'transfer' => $transfer,
            'cash' => $cash,
            'inPerson' => (int) $this->orders->filter(fn (Order $o) => $o->channel === SalesChannel::InPerson)->sum('total_cents'),
            'fees' => $fees,
            // Lo que pasa por el procesador o el banco (el efectivo queda en caja).
            'expectedDeposit' => $card + $transfer - $fees,
            'cost' => $cost,
            'deliveryCost' => $delivery,
            'profit' => $profit,
            'margin' => $gross > 0 ? round($profit / $gross * 100, 1) : null,
            'expenses' => $expenses,
            'cashExpenses' => $cashExpenses,
            'netProfit' => $netProfit,
            'netMargin' => $gross > 0 ? round($netProfit / $gross * 100, 1) : null,
            'averageTicket' => $count > 0 ? intdiv($gross, $count) : 0,
            'withoutCost' => $this->orders->whereNull('cost_cents')->count(),
            'customers' => $this->orders->pluck('customer_id')->filter()->unique()->count(),
            'cancelledPaidCount' => $this->cancelled->count(),
            'cancelledPaidTotal' => (int) $this->cancelled->sum('total_cents'),
        ];
    }

    /**
     * Ventas y margen por producto. Es el margen de producto: no descuenta promociones,
     * comisiones ni entregas, que se restan en la ganancia total.
     *
     * @return list<array{name: string, units: int, revenue: int, cost: int, profit: int, margin: ?float}>
     */
    public function byProduct(): array
    {
        $rows = [];
        foreach ($this->orders as $order) {
            foreach ($order->items as $item) {
                $row = $rows[$item->product_name] ?? ['name' => $item->product_name, 'units' => 0, 'revenue' => 0, 'cost' => 0];
                $row['units'] += $item->quantity;
                $row['revenue'] += $item->line_total_cents;
                $row['cost'] += (int) $item->cost_cents;
                $rows[$item->product_name] = $row;
            }
        }

        $rows = array_map(fn (array $r) => $r + [
            'profit' => $r['revenue'] - $r['cost'],
            'margin' => $r['revenue'] > 0 ? round(($r['revenue'] - $r['cost']) / $r['revenue'] * 100, 1) : null,
        ], array_values($rows));

        usort($rows, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        return $rows;
    }

    /** Piezas vendidas por sabor. @return array<string, int> */
    public function byFlavor(): array
    {
        $rolls = [];
        foreach ($this->orders as $order) {
            foreach ($order->items as $item) {
                /** @var OrderItem $item */
                foreach ($item->flavors ?? [] as $flavor) {
                    $name = $flavor['name'] ?? 'Sin nombre';
                    $rolls[$name] = ($rolls[$name] ?? 0) + ((int) ($flavor['count'] ?? 0)) * $item->quantity;
                }
            }
        }
        arsort($rolls);

        return $rolls;
    }

    /** Gastos por categoría, de mayor a menor. @return array<string, int> etiqueta => centavos */
    public function expensesByCategory(): array
    {
        $rows = [];
        foreach ($this->expenses as $expense) {
            $label = $expense->category->label();
            $rows[$label] = ($rows[$label] ?? 0) + $expense->amount_cents;
        }
        arsort($rows);

        return $rows;
    }

    /** @return array<string, array{orders: int, gross: int, profit: int, expenses: int, net: int}> fecha (Y-m-d) => totales */
    public function byDay(): array
    {
        $tz = config('rolobox.timezone');
        $empty = ['orders' => 0, 'gross' => 0, 'profit' => 0, 'expenses' => 0, 'net' => 0];
        $days = [];
        foreach ($this->orders as $order) {
            $day = $order->paid_at->timezone($tz)->toDateString();
            $days[$day] ??= $empty;
            $days[$day]['orders']++;
            $days[$day]['gross'] += $order->total_cents;
            $days[$day]['profit'] += (int) $order->profitCents();
        }
        foreach ($this->expenses as $expense) {
            $day = $expense->spent_on->toDateString();
            $days[$day] ??= $empty;
            $days[$day]['expenses'] += $expense->amount_cents;
        }
        foreach ($days as $day => $d) {
            $days[$day]['net'] = $d['profit'] - $d['expenses'];
        }
        ksort($days);

        return $days;
    }
}

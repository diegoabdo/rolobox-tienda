<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Pages\MonthlyReport;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\Quetzales;
use App\Models\Order;
use App\Services\SalesReport;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class SalesOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $today = CarbonImmutable::now(config('rolobox.timezone'))->startOfDay();

        // Ventas por día de los últimos 7 días (en quetzales, para la gráfica).
        $daily = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $today->subDays($i);
            $daily[] = $this->paidBetween($day, $day->endOfDay())->sum('total_cents') / 100;
        }

        $todayCents = (int) $this->paidBetween($today, $today->endOfDay())->sum('total_cents');
        $todayCount = $this->paidBetween($today, $today->endOfDay())->count();
        $weekCents = (int) round(array_sum($daily) * 100);

        $month = SalesReport::month($today->year, $today->month)->totals();

        $toPrepare = Order::whereIn('status', [OrderStatus::Paid->value, OrderStatus::Baking->value])->count();
        $awaiting = Order::where('status', OrderStatus::PendingPayment->value)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return [
            Stat::make('Ventas de hoy', Quetzales::format($todayCents))
                ->description($todayCount === 1 ? '1 pedido pagado' : "{$todayCount} pedidos pagados"),
            Stat::make('Últimos 7 días', Quetzales::format($weekCents))
                ->description('Pedidos pagados, sin cancelados')
                ->chart($daily)
                ->color('primary'),
            Stat::make('Ganancia neta del mes', Quetzales::format($month['netProfit']))
                ->description($month['netMargin'] === null ? 'Sin ventas todavía' : "Margen {$month['netMargin']}% · gastos ".Quetzales::format($month['expenses']))
                ->color($month['netProfit'] < 0 ? 'danger' : 'success')
                ->url(MonthlyReport::getUrl()),
            Stat::make('Por preparar', (string) $toPrepare)
                ->description('Pagados o en el horno')
                ->color($toPrepare > 0 ? 'warning' : 'gray')
                ->url(OrderResource::getUrl('index')),
            Stat::make('Esperando pago', (string) $awaiting)
                ->description('Creados en las últimas 24 horas'),
        ];
    }

    /** Pedidos pagados (no cancelados) entre dos momentos en hora de Guatemala. */
    private function paidBetween(CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return Order::query()
            ->whereNotNull('paid_at')
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->whereBetween('paid_at', [$from->utc(), $to->utc()]);
    }
}

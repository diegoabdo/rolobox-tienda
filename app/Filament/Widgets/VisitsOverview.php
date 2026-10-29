<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\PageView;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;

/** Visitantes de la tienda: hoy, la semana, el mes y cuántos terminan pagando. */
class VisitsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 4;

    protected ?string $pollingInterval = '60s';

    protected ?string $heading = 'Visitas a la tienda';

    protected function getStats(): array
    {
        $tz = config('rolobox.timezone');
        $today = CarbonImmutable::now($tz)->startOfDay();
        $from = $today->subDays(29);

        $views = PageView::query()
            ->where('viewed_at', '>=', $from->utc())
            ->get(['visitor', 'is_mobile', 'viewed_at']);

        // Un visitante se cuenta una vez por día: la huella cambia cada día a propósito.
        $byDay = $views->groupBy(fn (PageView $view): string => $view->viewed_at->setTimezone($tz)->toDateString());
        $visitorsOn = fn (CarbonImmutable $day): int => $byDay->get($day->toDateString(), collect())->unique('visitor')->count();

        $week = [];
        for ($i = 6; $i >= 0; $i--) {
            $week[] = $visitorsOn($today->subDays($i));
        }

        $month = $byDay->sum(fn (Collection $day): int => $day->unique('visitor')->count());
        $todayViews = $byDay->get($today->toDateString(), collect())->count();
        $mobileShare = $views->isEmpty() ? null : (int) round($views->where('is_mobile', true)->count() / $views->count() * 100);

        $paidOrders = Order::query()
            ->where('channel', 'online')
            ->whereNotNull('paid_at')
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->where('paid_at', '>=', $from->utc())
            ->count();

        return [
            Stat::make('Visitantes hoy', (string) end($week))
                ->description($todayViews === 1 ? '1 página vista' : "{$todayViews} páginas vistas"),
            Stat::make('Últimos 7 días', (string) array_sum($week))
                ->description('Visitantes por día, sumados')
                ->chart($week)
                ->color('primary'),
            Stat::make('Últimos 30 días', (string) $month)
                ->description($mobileShare === null ? 'Sin visitas todavía' : "{$mobileShare}% desde el teléfono"),
            Stat::make('Compran', $month > 0 ? round($paidOrders / $month * 100, 1).'%' : '—')
                ->description($paidOrders === 1 ? '1 pedido pagado en 30 días' : "{$paidOrders} pedidos pagados en 30 días"),
        ];
    }
}

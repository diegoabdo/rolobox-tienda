<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;

/** Cuántos roles de cada sabor hay que hornear hoy, mañana y pasado (pedidos pagados o en el horno). */
class ProductionPlan extends Widget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.production-plan';

    protected function getViewData(): array
    {
        $today = CarbonImmutable::now(config('rolobox.timezone'))->startOfDay();
        $last = $today->addDays(2);

        $orders = Order::query()
            ->with('items')
            ->whereIn('status', [OrderStatus::Paid->value, OrderStatus::Baking->value])
            ->whereDate('delivery_date', '>=', $today->toDateString())
            ->whereDate('delivery_date', '<=', $last->toDateString())
            ->get()
            ->groupBy(fn (Order $order): string => $order->delivery_date->toDateString());

        $days = [];
        for ($i = 0; $i <= 2; $i++) {
            $date = $today->addDays($i);
            $dayOrders = $orders->get($date->toDateString(), collect());

            $flavors = [];
            foreach ($dayOrders as $order) {
                foreach ($order->items as $item) {
                    foreach ($item->flavors ?? [] as $flavor) {
                        $name = $flavor['name'] ?? 'Sin nombre';
                        $flavors[$name] = ($flavors[$name] ?? 0) + ((int) ($flavor['count'] ?? 0)) * $item->quantity;
                    }
                }
            }
            arsort($flavors);

            $prefix = match ($i) {
                0 => 'Hoy',
                1 => 'Mañana',
                default => 'Pasado mañana',
            };

            $days[] = [
                'label' => $prefix.' · '.$date->locale('es')->isoFormat('ddd D [de] MMM'),
                'orders' => $dayOrders->count(),
                'rolls' => array_sum($flavors),
                'flavors' => $flavors,
            ];
        }

        return ['days' => $days];
    }
}

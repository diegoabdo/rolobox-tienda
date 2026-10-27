<?php

namespace App\Filament\Widgets;

use App\Models\PageView;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;

/** De los últimos 30 días: qué páginas se ven, de dónde llegan y cuántos avanzan hasta pagar. */
class VisitsBreakdown extends Widget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.visits-breakdown';

    /** Los pasos de una compra, en orden. */
    private const FUNNEL = ['home', 'box', 'cart.show', 'checkout.show', 'payments.return'];

    protected function getViewData(): array
    {
        $tz = config('rolobox.timezone');
        $from = CarbonImmutable::now($tz)->startOfDay()->subDays(29);

        $views = PageView::query()
            ->where('viewed_at', '>=', $from->utc())
            ->get(['page', 'visitor', 'referrer', 'is_mobile']);

        $pages = $views->groupBy('page')
            ->map(fn ($rows, string $page): array => [
                'label' => PageView::PAGES[$page] ?? $page,
                'views' => $rows->count(),
                'visitors' => $rows->unique('visitor')->count(),
            ])
            ->sortByDesc('views')
            ->values()
            ->all();

        $sources = $views->groupBy(fn (PageView $view): string => $view->referrer ?? 'Directo o sin dato')
            ->map(fn ($rows, string $source): array => ['label' => $source, 'visitors' => $rows->unique('visitor')->count()])
            ->sortByDesc('visitors')
            ->take(8)
            ->values()
            ->all();

        $funnel = array_map(fn (string $page): array => [
            'label' => PageView::PAGES[$page],
            'visitors' => $views->where('page', $page)->unique('visitor')->count(),
        ], self::FUNNEL);

        return [
            'total' => $views->count(),
            'pages' => $pages,
            'sources' => $sources,
            'funnel' => $funnel,
            'funnelTop' => max(1, max(array_column($funnel, 'visitors'))),
            'mobile' => $views->where('is_mobile', true)->count(),
        ];
    }
}

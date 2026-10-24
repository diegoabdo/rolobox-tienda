<?php

namespace App\Filament\Pages;

use App\Services\SalesReport;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/** Ventas, costos, gastos y ganancias de un mes, por producto, sabor, categoría de gasto y día. Se puede descargar en CSV. */
class MonthlyReport extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string | UnitEnum | null $navigationGroup = 'Finanzas';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Reporte mensual';

    protected static ?string $slug = 'reporte-mensual';

    protected string $view = 'filament.pages.monthly-report';

    /** Mes que se está viendo, en formato AAAA-MM. */
    public string $month = '';

    public function mount(): void
    {
        $this->month = $this->now()->format('Y-m');
    }

    public function getSubheading(): string
    {
        return ucfirst($this->firstDay()->locale('es')->isoFormat('MMMM [de] YYYY'));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('previous')
                ->label('Mes anterior')
                ->icon(Heroicon::OutlinedChevronLeft)
                ->color('gray')
                ->action(fn () => $this->month = $this->firstDay()->subMonth()->format('Y-m')),
            Action::make('next')
                ->label('Mes siguiente')
                ->icon(Heroicon::OutlinedChevronRight)
                ->color('gray')
                ->disabled(fn (): bool => $this->month >= $this->now()->format('Y-m'))
                ->action(fn () => $this->month = $this->firstDay()->addMonth()->format('Y-m')),
            Action::make('csv')
                ->label('Descargar CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn (): StreamedResponse => $this->downloadCsv()),
        ];
    }

    protected function getViewData(): array
    {
        $report = $this->report();

        return [
            'totals' => $report->totals(),
            'products' => $report->byProduct(),
            'flavors' => $report->byFlavor(),
            'expenses' => $report->expensesByCategory(),
            'days' => $report->byDay(),
        ];
    }

    private function report(): SalesReport
    {
        $first = $this->firstDay();

        return SalesReport::month($first->year, $first->month);
    }

    private function firstDay(): CarbonImmutable
    {
        // El mes viene del navegador: si no tiene el formato esperado se usa el mes actual.
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->month)) {
            $this->month = $this->now()->format('Y-m');
        }

        return CarbonImmutable::createFromFormat('Y-m-d', $this->month.'-01', config('rolobox.timezone'))->startOfDay();
    }

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::now(config('rolobox.timezone'));
    }

    private function downloadCsv(): StreamedResponse
    {
        $report = $this->report();
        $t = $report->totals();
        $q = fn (?int $cents): string => $cents === null ? '' : number_format($cents / 100, 2, '.', '');

        $rows = [
            ['Reporte RoloBox', $this->getSubheading()],
            [],
            ['Resumen', 'Monto (Q)'],
            ['Pedidos pagados', $t['orders']],
            ['Ventas (cobrado)', $q($t['gross'])],
            ['Con tarjeta', $q($t['card'])],
            ['Con transferencia', $q($t['transfer'])],
            ['En efectivo', $q($t['cash'])],
            ['Ventas en persona', $q($t['inPerson'])],
            ['Descuentos por promociones', $q($t['discounts'])],
            ['Envíos cobrados', $q($t['shipping'])],
            ['Comisiones del procesador', $q($t['fees'])],
            ['Costo de productos', $q($t['cost'])],
            ['Costo de entregas', $q($t['deliveryCost'])],
            ['Ganancia antes de gastos', $q($t['profit'])],
            ['Gastos del negocio', $q($t['expenses'])],
            ['Ganancia neta', $q($t['netProfit'])],
            ['Margen neto (%)', $t['netMargin'] ?? ''],
            ['Ticket promedio', $q($t['averageTicket'])],
            ['Clientes', $t['customers']],
            [],
            ['Producto', 'Unidades', 'Ventas (Q)', 'Costo (Q)', 'Margen (Q)', 'Margen (%)'],
        ];
        foreach ($report->byProduct() as $p) {
            $rows[] = [$p['name'], $p['units'], $q($p['revenue']), $q($p['cost']), $q($p['profit']), $p['margin'] ?? ''];
        }
        $rows[] = [];
        $rows[] = ['Sabor', 'Roles vendidos'];
        foreach ($report->byFlavor() as $name => $rolls) {
            $rows[] = [$name, $rolls];
        }
        $rows[] = [];
        $rows[] = ['Gasto por categoría', 'Monto (Q)'];
        foreach ($report->expensesByCategory() as $category => $cents) {
            $rows[] = [$category, $q($cents)];
        }
        $rows[] = [];
        $rows[] = ['Fecha', 'Categoría', 'Descripción', 'Pagado con', 'Monto (Q)'];
        foreach ($report->expenses() as $expense) {
            $rows[] = [$expense->spent_on->toDateString(), $expense->category->label(), $expense->description, $expense->payment_method->label(), $q($expense->amount_cents)];
        }
        $rows[] = [];
        $rows[] = ['Día', 'Pedidos', 'Ventas (Q)', 'Ganancia (Q)', 'Gastos (Q)', 'Ganancia neta (Q)'];
        foreach ($report->byDay() as $day => $d) {
            $rows[] = [$day, $d['orders'], $q($d['gross']), $q($d['profit']), $q($d['expenses']), $q($d['net'])];
        }

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel lea bien las tildes.
            foreach ($rows as $row) {
                fputcsv($out, $row, escape: '');
            }
            fclose($out);
        }, "rolobox-reporte-{$this->month}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

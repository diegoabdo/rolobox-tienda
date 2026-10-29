@php
    $q = fn (?int $cents): string => $cents === null ? '—' : 'Q '.number_format($cents / 100, 2);
    $cell = 'padding: 0.5rem 0.75rem; border-bottom: 1px solid rgba(127, 127, 127, 0.2);';
    $num = $cell.' text-align: right; white-space: nowrap;';
    $head = $cell.' font-weight: 600; font-size: 0.8rem; opacity: 0.7;';
    $kpis = [
        ['Ventas', $q($totals['gross']), $totals['orders'].' pedidos pagados · '.$q($totals['inPerson']).' en persona'],
        ['Ganancia neta', $q($totals['netProfit']), $totals['netMargin'] === null ? 'Sin ventas' : 'Margen neto '.$totals['netMargin'].'%'],
        ['Gastos', $q($totals['expenses']), $q($totals['cashExpenses']).' en efectivo'],
        ['Ticket promedio', $q($totals['averageTicket']), $totals['customers'].' clientes'],
    ];
@endphp

<x-filament-panels::page>
    <div style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr));">
        @foreach ($kpis as [$label, $value, $hint])
            <x-filament::section>
                <p style="font-size: 0.875rem; opacity: 0.7;">{{ $label }}</p>
                <p style="font-size: 1.6rem; font-weight: 700; line-height: 1.3;">{{ $value }}</p>
                <p style="font-size: 0.8rem; opacity: 0.7;">{{ $hint }}</p>
            </x-filament::section>
        @endforeach
    </div>

    @if ($totals['withoutCost'] > 0)
        <x-filament::section>
            <p style="font-size: 0.875rem;">
                {{ $totals['withoutCost'] }} {{ $totals['withoutCost'] === 1 ? 'pedido no tiene' : 'pedidos no tienen' }} costos registrados
                (se hicieron antes de cargar los costos), así que la ganancia real es menor a la que se muestra.
            </p>
        </x-filament::section>
    @endif

    @if ($totals['cancelledPaidCount'] > 0)
        <x-filament::section>
            <p style="font-size: 0.875rem;">
                Además hubo {{ $totals['cancelledPaidCount'] }} {{ $totals['cancelledPaidCount'] === 1 ? 'pedido pagado y luego cancelado' : 'pedidos pagados y luego cancelados' }}
                por {{ $q($totals['cancelledPaidTotal']) }}. No se cuentan como ventas: revisa si se devolvió el dinero.
            </p>
        </x-filament::section>
    @endif

    <div style="display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(22rem, 1fr));">
        <x-filament::section heading="Estado de resultados" description="Del total cobrado a la ganancia.">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                <tbody>
                    <tr><td style="{{ $cell }}">Productos vendidos</td><td style="{{ $num }}">{{ $q($totals['subtotal']) }}</td></tr>
                    <tr><td style="{{ $cell }}">− Descuentos por promociones</td><td style="{{ $num }}">{{ $q($totals['discounts']) }}</td></tr>
                    <tr><td style="{{ $cell }}">+ Envíos cobrados</td><td style="{{ $num }}">{{ $q($totals['shipping']) }}</td></tr>
                    <tr><td style="{{ $cell }} font-weight: 600;">= Total cobrado</td><td style="{{ $num }} font-weight: 600;">{{ $q($totals['gross']) }}</td></tr>
                    <tr><td style="{{ $cell }} padding-left: 1.5rem; opacity: 0.75;">con tarjeta</td><td style="{{ $num }} opacity: 0.75;">{{ $q($totals['card']) }}</td></tr>
                    <tr><td style="{{ $cell }} padding-left: 1.5rem; opacity: 0.75;">con transferencia</td><td style="{{ $num }} opacity: 0.75;">{{ $q($totals['transfer']) }}</td></tr>
                    <tr><td style="{{ $cell }} padding-left: 1.5rem; opacity: 0.75;">en efectivo</td><td style="{{ $num }} opacity: 0.75;">{{ $q($totals['cash']) }}</td></tr>
                    <tr><td style="{{ $cell }}">− Comisiones del procesador</td><td style="{{ $num }}">{{ $q($totals['fees']) }}</td></tr>
                    <tr><td style="{{ $cell }}">− Costo de productos</td><td style="{{ $num }}">{{ $q($totals['cost']) }}</td></tr>
                    <tr><td style="{{ $cell }}">− Costo de entregas</td><td style="{{ $num }}">{{ $q($totals['deliveryCost']) }}</td></tr>
                    <tr><td style="{{ $cell }} font-weight: 600;">= Ganancia antes de gastos</td><td style="{{ $num }} font-weight: 600;">{{ $q($totals['profit']) }}</td></tr>
                    <tr><td style="{{ $cell }}">− Gastos del negocio</td><td style="{{ $num }}">{{ $q($totals['expenses']) }}</td></tr>
                    <tr><td style="{{ $cell }} font-weight: 700;">= Ganancia neta</td><td style="{{ $num }} font-weight: 700;">{{ $q($totals['netProfit']) }}</td></tr>
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section heading="Gastos por categoría">
            @if ($expenses === [])
                <p style="font-size: 0.875rem; opacity: 0.7;">Sin gastos registrados este mes.</p>
            @else
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <tbody>
                        @foreach ($expenses as $category => $cents)
                            <tr><td style="{{ $cell }}">{{ $category }}</td><td style="{{ $num }} font-weight: 600;">{{ $q($cents) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>

        <x-filament::section heading="Piezas vendidas por sabor">
            @if ($flavors === [])
                <p style="font-size: 0.875rem; opacity: 0.7;">Sin ventas este mes.</p>
            @else
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <tbody>
                        @foreach ($flavors as $name => $rolls)
                            <tr><td style="{{ $cell }}">{{ $name }}</td><td style="{{ $num }} font-weight: 600;">{{ $rolls }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>
    </div>

    <x-filament::section heading="Por producto" description="Margen de producto: precio de venta menos costo de ingredientes y empaque. Las promociones, comisiones y entregas se restan en la ganancia total.">
        @if ($products === [])
            <p style="font-size: 0.875rem; opacity: 0.7;">Sin ventas este mes.</p>
        @else
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr>
                            <th style="{{ $head }} text-align: left;">Producto</th>
                            <th style="{{ $head }} text-align: right;">Unidades</th>
                            <th style="{{ $head }} text-align: right;">Ventas</th>
                            <th style="{{ $head }} text-align: right;">Costo</th>
                            <th style="{{ $head }} text-align: right;">Margen</th>
                            <th style="{{ $head }} text-align: right;">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $p)
                            <tr>
                                <td style="{{ $cell }} font-weight: 600;">{{ $p['name'] }}</td>
                                <td style="{{ $num }}">{{ $p['units'] }}</td>
                                <td style="{{ $num }}">{{ $q($p['revenue']) }}</td>
                                <td style="{{ $num }}">{{ $q($p['cost']) }}</td>
                                <td style="{{ $num }} font-weight: 600;">{{ $q($p['profit']) }}</td>
                                <td style="{{ $num }}">{{ $p['margin'] === null ? '—' : $p['margin'].'%' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    <x-filament::section heading="Por día" collapsible>
        @if ($days === [])
            <p style="font-size: 0.875rem; opacity: 0.7;">Sin ventas ni gastos este mes.</p>
        @else
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr>
                            <th style="{{ $head }} text-align: left;">Día</th>
                            <th style="{{ $head }} text-align: right;">Pedidos</th>
                            <th style="{{ $head }} text-align: right;">Ventas</th>
                            <th style="{{ $head }} text-align: right;">Ganancia</th>
                            <th style="{{ $head }} text-align: right;">Gastos</th>
                            <th style="{{ $head }} text-align: right;">Neta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($days as $day => $d)
                            <tr>
                                <td style="{{ $cell }}">{{ \Carbon\CarbonImmutable::parse($day)->locale('es')->isoFormat('ddd D [de] MMM') }}</td>
                                <td style="{{ $num }}">{{ $d['orders'] }}</td>
                                <td style="{{ $num }}">{{ $q($d['gross']) }}</td>
                                <td style="{{ $num }}">{{ $q($d['profit']) }}</td>
                                <td style="{{ $num }}">{{ $q($d['expenses']) }}</td>
                                <td style="{{ $num }} font-weight: 600;">{{ $q($d['net']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>

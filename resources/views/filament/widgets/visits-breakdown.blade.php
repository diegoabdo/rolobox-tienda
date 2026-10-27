<x-filament-widgets::widget>
    <x-filament::section
        heading="De dónde vienen y qué ven"
        description="Últimos 30 días. Sin cookies: un visitante se cuenta una vez por día."
    >
        @if ($total === 0)
            <p style="font-size: 0.875rem; opacity: 0.7;">Todavía no hay visitas registradas.</p>
        @else
            <div style="display: grid; gap: 2rem; grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));">
                <div>
                    <p style="font-weight: 600; margin-bottom: 0.5rem;">Recorrido hasta pagar</p>
                    <ul style="display: grid; gap: 0.6rem;">
                        @foreach ($funnel as $step)
                            <li style="font-size: 0.875rem;">
                                <div style="display: flex; justify-content: space-between; gap: 1rem;">
                                    <span>{{ $step['label'] }}</span>
                                    <strong>{{ $step['visitors'] }}</strong>
                                </div>
                                <div style="margin-top: 0.25rem; height: 0.4rem; border-radius: 9999px; background: rgba(127, 127, 127, 0.2);">
                                    <div style="height: 100%; border-radius: 9999px; background: #b14b1c; width: {{ round($step['visitors'] / $funnelTop * 100) }}%;"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <p style="font-weight: 600; margin-bottom: 0.5rem;">Páginas más vistas</p>
                    <ul style="display: grid; gap: 0.25rem;">
                        @foreach ($pages as $page)
                            <li style="display: flex; justify-content: space-between; gap: 1rem; font-size: 0.875rem;">
                                <span>{{ $page['label'] }}</span>
                                <span><strong>{{ $page['views'] }}</strong> <span style="opacity: 0.6;">· {{ $page['visitors'] }} visitantes</span></span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <p style="font-weight: 600; margin-bottom: 0.5rem;">De dónde llegan</p>
                    <ul style="display: grid; gap: 0.25rem;">
                        @foreach ($sources as $source)
                            <li style="display: flex; justify-content: space-between; gap: 1rem; font-size: 0.875rem;">
                                <span>{{ $source['label'] }}</span>
                                <strong>{{ $source['visitors'] }}</strong>
                            </li>
                        @endforeach
                    </ul>
                    <p style="font-size: 0.875rem; opacity: 0.7; margin-top: 1rem;">
                        {{ round($mobile / $total * 100) }}% de las páginas se vieron desde un teléfono.
                    </p>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

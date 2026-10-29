<x-filament-widgets::widget>
    <x-filament::section
        heading="Qué hornear"
        description="Piezas por sabor de los pedidos pagados o en el horno, según el día de entrega."
    >
        <div style="display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr));">
            @foreach ($days as $day)
                <div>
                    <p style="font-weight: 600;">{{ $day['label'] }}</p>
                    <p style="font-size: 0.875rem; opacity: 0.7; margin-bottom: 0.5rem;">
                        {{ $day['orders'] }} {{ $day['orders'] === 1 ? 'pedido' : 'pedidos' }} · {{ $day['rolls'] }} piezas
                    </p>

                    @if ($day['flavors'] === [])
                        <p style="font-size: 0.875rem; opacity: 0.6;">Nada por hornear.</p>
                    @else
                        <ul style="display: grid; gap: 0.25rem;">
                            @foreach ($day['flavors'] as $name => $count)
                                <li style="display: flex; justify-content: space-between; gap: 1rem; font-size: 0.875rem;">
                                    <span>{{ $name }}</span>
                                    <strong>{{ $count }}</strong>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\Labels;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Phone;
use Carbon\CarbonImmutable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Pedidos pagados por entregar: atrasados, de hoy y de los próximos 7 días, más los de paquetería. */
class UpcomingDeliveries extends TableWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $limit = CarbonImmutable::now(config('rolobox.timezone'))->addDays(7)->toDateString();

        return $table
            ->heading('Entregas próximas')
            ->poll('60s')
            ->query(
                Order::query()
                    ->with('items')
                    ->whereIn('status', [
                        OrderStatus::Paid->value,
                        OrderStatus::Baking->value,
                        OrderStatus::OutForDelivery->value,
                        OrderStatus::Shipped->value,
                    ])
                    ->where(fn ($q) => $q->whereNull('delivery_date')->orWhereDate('delivery_date', '<=', $limit))
            )
            ->defaultSort(fn ($query) => $query
                ->orderByRaw('delivery_date is null')
                ->orderBy('delivery_date')
                ->orderBy('delivery_slot'))
            ->columns([
                TextColumn::make('delivery_date')
                    ->label('Entrega')
                    ->date('D d/m')
                    ->placeholder('Paquetería')
                    ->description(fn (Order $record): ?string => $record->delivery_slot ? Labels::slot($record->delivery_slot) : null)
                    // Atrasado (fecha de entrega anterior a hoy en Guatemala): en rojo.
                    ->color(fn (Order $record): ?string => $record->delivery_date
                        && $record->delivery_date->toDateString() < CarbonImmutable::now(config('rolobox.timezone'))->toDateString()
                        ? 'danger'
                        : null),
                TextColumn::make('reference')
                    ->label('Código')
                    ->weight('bold'),
                TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->description(fn (Order $record): ?string => $record->customer_phone ? Phone::display($record->customer_phone) : null),
                TextColumn::make('items')
                    ->label('Productos')
                    ->getStateUsing(fn (Order $record): array => $record->items
                        ->map(fn (OrderItem $item): string => "{$item->quantity} × {$item->product_name}")
                        ->all())
                    ->listWithLineBreaks(),
                TextColumn::make('zone_name')
                    ->label('Zona')
                    ->limit(24),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => Labels::orderStatus($state))
                    ->color(fn ($state): string => Labels::orderStatusColor($state)),
            ])
            ->recordUrl(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('No hay entregas pendientes')
            ->paginated([10, 25]);
    }
}

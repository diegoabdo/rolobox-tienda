<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\Labels;
use App\Models\Order;
use App\Models\OrderItem;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Historial de pedidos del cliente (también los que no se pagaron). */
class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Pedidos';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        $tz = config('rolobox.timezone');

        return $table
            ->recordTitleAttribute('reference')
            ->modifyQueryUsing(fn ($query) => $query->with('items'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->label('Código')
                    ->weight('bold'),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y')
                    ->timezone($tz)
                    ->sortable(),
                TextColumn::make('items')
                    ->label('Productos')
                    ->getStateUsing(fn (Order $record): array => $record->items
                        ->map(fn (OrderItem $item): string => "{$item->quantity} × {$item->product_name}")
                        ->all())
                    ->listWithLineBreaks(),
                TextColumn::make('total_cents')
                    ->label('Total')
                    ->money('GTQ', divideBy: 100),
                TextColumn::make('payment_method')
                    ->label('Pago')
                    ->formatStateUsing(fn ($state): string => Labels::paymentMethod($state)),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => Labels::orderStatus($state))
                    ->color(fn ($state): string => Labels::orderStatusColor($state)),
            ])
            ->recordUrl(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record]));
    }
}

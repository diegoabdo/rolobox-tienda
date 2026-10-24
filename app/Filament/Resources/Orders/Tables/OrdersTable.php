<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Filament\Resources\Orders\OrderActions;
use App\Filament\Support\Labels;
use App\Models\Order;
use App\Support\Phone;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        $tz = config('rolobox.timezone');

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->label('Código')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->searchable()
                    ->description(fn (Order $record): ?string => $record->customer_phone ? Phone::display($record->customer_phone) : null),
                TextColumn::make('customer_phone')
                    ->label('WhatsApp')
                    ->searchable()
                    ->formatStateUsing(fn (?string $state): string => $state ? Phone::display($state) : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('delivery_date')
                    ->label('Entrega')
                    ->date('d/m/Y')
                    ->placeholder('Paquetería')
                    ->description(fn (Order $record): ?string => $record->delivery_slot ? Labels::slot($record->delivery_slot) : null)
                    ->sortable(),
                TextColumn::make('zone_name')
                    ->label('Zona')
                    ->limit(28)
                    ->toggleable(),
                TextColumn::make('total_cents')
                    ->label('Total')
                    ->money('GTQ', divideBy: 100)
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Pago')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state): string => Labels::paymentMethod($state))
                    ->description(fn (Order $record): ?string => Labels::value($record->channel) === 'in_person' ? 'En persona' : null)
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => Labels::orderStatus($state))
                    ->color(fn ($state): string => Labels::orderStatusColor($state)),
                TextColumn::make('paid_at')
                    ->label('Pagado')
                    ->dateTime('d/m/Y H:i')
                    ->timezone($tz)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->timezone($tz)
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(Labels::orderStatuses())
                    ->multiple(),
                SelectFilter::make('delivery_type')
                    ->label('Tipo de entrega')
                    ->options(Labels::deliveryTypes()),
                SelectFilter::make('payment_method')
                    ->label('Forma de pago')
                    ->options(Labels::paymentMethods()),
                SelectFilter::make('channel')
                    ->label('Canal')
                    ->options(Labels::channels()),
                Filter::make('delivery_date')
                    ->label('Día de entrega')
                    ->schema([
                        DatePicker::make('from')->label('Entrega desde'),
                        DatePicker::make('until')->label('Entrega hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('delivery_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('delivery_date', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make(OrderActions::all()),
            ]);
    }
}

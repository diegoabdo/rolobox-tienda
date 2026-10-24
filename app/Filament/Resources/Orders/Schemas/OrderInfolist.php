<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Filament\Support\Labels;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Phone;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $tz = config('rolobox.timezone');

        return $schema
            ->columns(3)
            ->components([
                Section::make('Productos')
                    ->columnSpan(2)
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->columns(4)
                            ->schema([
                                TextEntry::make('product_name')
                                    ->label('Producto')
                                    ->weight('bold'),
                                TextEntry::make('quantity')
                                    ->label('Cantidad'),
                                TextEntry::make('flavors')
                                    ->label('Sabores')
                                    ->getStateUsing(fn (OrderItem $record): array => collect($record->flavors)
                                        ->map(fn (array $flavor): string => "{$flavor['count']} × {$flavor['name']}")
                                        ->all())
                                    ->listWithLineBreaks(),
                                TextEntry::make('line_total_cents')
                                    ->label('Total')
                                    ->money('GTQ', divideBy: 100),
                                TextEntry::make('gift_message')
                                    ->label('Mensaje de regalo')
                                    ->placeholder('Sin mensaje')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Pedido')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('reference')
                            ->label('Código')
                            ->copyable()
                            ->weight('bold'),
                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => Labels::orderStatus($state))
                            ->color(fn ($state): string => Labels::orderStatusColor($state)),
                        TextEntry::make('created_at')
                            ->label('Creado')
                            ->dateTime('d/m/Y H:i')
                            ->timezone($tz),
                        TextEntry::make('paid_at')
                            ->label('Pagado')
                            ->dateTime('d/m/Y H:i')
                            ->timezone($tz)
                            ->placeholder('Sin pagar'),
                        TextEntry::make('payment_method')
                            ->label('Forma de pago elegida')
                            ->formatStateUsing(fn ($state): string => Labels::paymentMethod($state)),
                        TextEntry::make('channel')
                            ->label('Canal')
                            ->formatStateUsing(fn ($state): string => Labels::channel($state)),
                    ]),

                Section::make('Entrega')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('delivery_type')
                            ->label('Tipo')
                            ->formatStateUsing(fn ($state): string => Labels::deliveryType($state)),
                        TextEntry::make('zone_name')
                            ->label('Zona'),
                        TextEntry::make('delivery_date')
                            ->label('Día')
                            ->date('d/m/Y')
                            ->placeholder('Paquetería'),
                        TextEntry::make('delivery_slot')
                            ->label('Horario')
                            ->formatStateUsing(fn (?string $state): string => Labels::slot($state))
                            ->placeholder('—'),
                        TextEntry::make('address')
                            ->label('Dirección')
                            ->columnSpanFull(),
                        TextEntry::make('address_reference')
                            ->label('Referencias')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('notes')
                            ->label('Notas del cliente')
                            ->placeholder('Sin notas')
                            ->columnSpanFull(),
                    ]),

                Section::make('Cliente')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('customer_name')
                            ->label('Nombre'),
                        TextEntry::make('customer_phone')
                            ->label('WhatsApp')
                            ->formatStateUsing(fn (?string $state): string => $state ? Phone::display($state) : '—')
                            ->url(fn (Order $record): ?string => $record->customer_phone ? 'https://wa.me/'.$record->customer_phone : null)
                            ->placeholder('—')
                            ->openUrlInNewTab()
                            ->color('primary'),
                        TextEntry::make('customer_email')
                            ->label('Correo')
                            ->placeholder('—'),
                        TextEntry::make('nit')
                            ->label('NIT'),
                    ]),

                Section::make('Totales')
                    ->columnSpan(2)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('subtotal_cents')
                            ->label('Subtotal')
                            ->money('GTQ', divideBy: 100),
                        TextEntry::make('discount_cents')
                            ->label('Descuento')
                            ->money('GTQ', divideBy: 100)
                            ->helperText(fn (Order $record): ?string => $record->promotion_code ? "Cupón {$record->promotion_code}" : null),
                        TextEntry::make('shipping_cents')
                            ->label('Envío')
                            ->money('GTQ', divideBy: 100),
                        TextEntry::make('total_cents')
                            ->label('Total')
                            ->money('GTQ', divideBy: 100)
                            ->weight('bold'),
                    ]),

                Section::make('Pagos')
                    ->columnSpan(1)
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('payments')
                            ->hiddenLabel()
                            ->placeholder('Sin intentos de pago')
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->formatStateUsing(fn ($state): string => Labels::paymentStatuses()[Labels::value($state)] ?? '—'),
                                TextEntry::make('amount_cents')
                                    ->label('Monto')
                                    ->money('GTQ', divideBy: 100),
                                TextEntry::make('method')
                                    ->label('Pagado con')
                                    ->formatStateUsing(fn ($state): string => Labels::paymentMethod($state))
                                    ->placeholder('—'),
                                TextEntry::make('card_last4')
                                    ->label('Tarjeta')
                                    ->formatStateUsing(fn (?string $state, $record): string => trim(($record->card_brand ?? '').' ····'.$state))
                                    ->placeholder('—'),
                                TextEntry::make('provider_checkout_id')
                                    ->label('Checkout')
                                    ->helperText(fn ($record): string => $record->provider)
                                    ->copyable()
                                    ->placeholder('—'),
                            ]),
                    ]),

                Section::make('Ganancia')
                    ->columnSpanFull()
                    ->columns(5)
                    ->description('Estimada: costos registrados al vender y comisión según la tarifa configurada.')
                    ->schema([
                        TextEntry::make('total_cents')
                            ->label('Cobrado')
                            ->money('GTQ', divideBy: 100),
                        TextEntry::make('cost_cents')
                            ->label('Costo productos')
                            ->money('GTQ', divideBy: 100)
                            ->placeholder('Sin registrar'),
                        TextEntry::make('payment_fee_cents')
                            ->label('Comisión')
                            ->money('GTQ', divideBy: 100)
                            ->placeholder('Al pagarse'),
                        TextEntry::make('delivery_cost_cents')
                            ->label('Costo entrega')
                            ->money('GTQ', divideBy: 100)
                            ->placeholder('—'),
                        TextEntry::make('profit')
                            ->label('Ganancia')
                            ->state(fn (Order $record): ?int => $record->profitCents())
                            ->money('GTQ', divideBy: 100)
                            ->weight('bold')
                            ->color(fn (Order $record): string => ($record->profitCents() ?? 0) < 0 ? 'danger' : 'success')
                            ->placeholder('—'),
                    ]),

                Section::make('Constancia')
                    ->columnSpanFull()
                    ->columns(3)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('legal_version')
                            ->label('Versión de términos aceptada'),
                        TextEntry::make('accepted_at')
                            ->label('Aceptados el')
                            ->dateTime('d/m/Y H:i')
                            ->timezone($tz),
                        TextEntry::make('closed_at')
                            ->label('Cerrado el')
                            ->dateTime('d/m/Y H:i')
                            ->timezone($tz)
                            ->placeholder('Abierto'),
                    ]),
            ]);
    }
}

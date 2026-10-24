<?php

namespace App\Filament\Resources\Orders;

use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Filament\Support\Quetzales;
use App\Models\Customer;
use App\Models\Flavor;
use App\Models\Product;
use App\Services\InPersonSale;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

/**
 * Botón "Registrar venta en persona": efectivo, POS o transferencia directa. La venta queda como
 * un pedido pagado y entregado, con sus costos, para los reportes y el cierre de caja.
 */
class InPersonSaleAction
{
    public static function make(): Action
    {
        $tz = config('rolobox.timezone');
        $productType = fn (Get $get): ?string => Product::find($get('product_id'))?->type->value;

        return Action::make('inPersonSale')
            ->label('Registrar venta en persona')
            ->icon(Heroicon::OutlinedBanknotes)
            ->modalHeading('Venta en persona')
            ->modalDescription('Se registra como pagada y entregada. Precios: los de tarjeta si cobras con POS; los de transferencia si es efectivo o transferencia.')
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalSubmitActionLabel('Registrar venta')
            ->schema([
                Grid::make(3)->schema([
                    DatePicker::make('sold_on')
                        ->label('Fecha')
                        ->default(fn (): string => CarbonImmutable::now($tz)->toDateString())
                        ->maxDate(fn (): string => CarbonImmutable::now($tz)->toDateString())
                        ->required(),
                    Select::make('payment_method')
                        ->label('Cobro')
                        ->options([
                            PaymentMethod::Cash->value => 'Efectivo',
                            PaymentMethod::Card->value => 'Tarjeta (POS)',
                            PaymentMethod::BankTransfer->value => 'Transferencia',
                        ])
                        ->default(PaymentMethod::Cash->value)
                        ->required(),
                    Quetzales::input('discount_cents')
                        ->label('Descuento (opcional)')
                        ->default(null),
                ]),

                Section::make('Cliente (opcional)')
                    ->description('Elige uno que ya compró, o escribe su WhatsApp para guardarlo. Puedes dejarlo vacío.')
                    ->columns(3)
                    ->compact()
                    ->schema([
                        Select::make('customer_id')
                            ->label('Cliente registrado')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Customer::query()
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', '%'.preg_replace('/\D+/', '', $search).'%')
                                ->orderBy('name')
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn (Customer $c): array => [$c->id => "{$c->name} · {$c->phone}"])
                                ->all())
                            ->getOptionLabelUsing(fn ($value): ?string => Customer::find($value)?->name)
                            ->live(),
                        TextInput::make('customer_name')
                            ->label('Nombre')
                            ->maxLength(120)
                            ->hidden(fn (Get $get): bool => filled($get('customer_id'))),
                        TextInput::make('customer_phone')
                            ->label('WhatsApp')
                            ->tel()
                            ->placeholder('5555 5555')
                            ->hidden(fn (Get $get): bool => filled($get('customer_id'))),
                    ]),

                Repeater::make('items')
                    ->label('Productos')
                    ->addActionLabel('Agregar producto')
                    ->defaultItems(1)
                    ->minItems(1)
                    ->columns(3)
                    ->schema([
                        Select::make('product_id')
                            ->label('Producto')
                            ->options(fn (): array => Product::where('published', true)->orderBy('sort_order')->pluck('name', 'id')->all())
                            ->required()
                            ->live(),
                        TextInput::make('quantity')
                            ->label('Cantidad')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                        Select::make('flavor_id')
                            ->label('Sabor')
                            ->options(fn (): array => Flavor::orderBy('sort_order')->pluck('name', 'id')->all())
                            ->visible(fn (Get $get): bool => $productType($get) === ProductType::Single->value)
                            ->required(fn (Get $get): bool => $productType($get) === ProductType::Single->value),
                        Repeater::make('flavors')
                            ->label(fn (Get $get): string => 'Sabores de la caja ('.(Product::find($get('product_id'))?->box_size ?? 6).' roles)')
                            ->addActionLabel('Agregar sabor')
                            ->columns(2)
                            ->columnSpanFull()
                            ->defaultItems(1)
                            ->visible(fn (Get $get): bool => $productType($get) === ProductType::Box->value)
                            ->schema([
                                Select::make('flavor_id')
                                    ->label('Sabor')
                                    ->options(fn (): array => Flavor::orderBy('sort_order')->pluck('name', 'id')->all())
                                    ->required(),
                                TextInput::make('count')
                                    ->label('Roles')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),
                            ]),
                    ]),

                Textarea::make('notes')
                    ->label('Notas')
                    ->rows(2)
                    ->placeholder('Ej.: feria del sábado, pedido por teléfono.'),
            ])
            ->action(function (array $data, Action $action): void {
                try {
                    $order = app(InPersonSale::class)->record([
                        ...$data,
                        'items' => array_values($data['items'] ?? []),
                    ], auth()->id());
                } catch (ValidationException $e) {
                    Notification::make()
                        ->title('No se registró la venta')
                        ->body(collect($e->errors())->flatten()->first())
                        ->danger()
                        ->send();

                    $action->halt();

                    return;
                }

                Notification::make()
                    ->title("Venta {$order->reference} registrada")
                    ->body('Total '.Quetzales::format($order->total_cents).' · '.$order->payment_method->label())
                    ->success()
                    ->send();
            });
    }
}

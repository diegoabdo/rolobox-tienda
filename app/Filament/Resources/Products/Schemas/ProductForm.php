<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\BoxScope;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Filament\Support\Labels;
use App\Filament\Support\Quetzales;
use App\Models\Product;
use App\Services\Costs;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    /** Una caja mixta no pertenece a una sola categoría. */
    private static function isMixedBox(Get $get): bool
    {
        return Labels::value($get('type')) === ProductType::Box->value
            && Labels::value($get('box_scope')) === BoxScope::Mixed->value;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Producto')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Identificador en la URL')
                            ->required()
                            ->maxLength(120)
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        Select::make('type')
                            ->label('¿Cómo se vende?')
                            ->options(Labels::productTypes())
                            ->helperText(fn (Get $get): string => match (Labels::value($get('type'))) {
                                ProductType::Box->value => 'El cliente arma una caja eligiendo sabores.',
                                ProductType::Single->value => 'El cliente elige un sabor y lo pide por unidad (ej.: Croissant, Trenza completa, Porción).',
                                default => 'Suelto: una pieza o presentación. Caja: varias piezas a elegir.',
                            })
                            ->required()
                            ->live(),
                        Select::make('box_scope')
                            ->label('¿Qué lleva la caja?')
                            ->options(fn (): array => collect(BoxScope::cases())->mapWithKeys(fn (BoxScope $scope): array => [$scope->value => $scope->label()])->all())
                            ->default(BoxScope::Category->value)
                            ->helperText(fn (Get $get): string => Labels::value($get('box_scope')) === BoxScope::Mixed->value
                                ? 'Mezcla varias categorías. Entran los sabores marcados «En caja mixta» en Sabores.'
                                : 'Solo sabores de su categoría marcados «En su caja» en Sabores.')
                            ->required(fn (Get $get): bool => Labels::value($get('type')) === ProductType::Box->value)
                            ->visible(fn (Get $get): bool => Labels::value($get('type')) === ProductType::Box->value)
                            ->live(),
                        Select::make('category_id')
                            ->label('Categoría')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Define de qué sabores se arma o se pide.')
                            ->required(fn (Get $get): bool => ! self::isMixedBox($get))
                            ->visible(fn (Get $get): bool => ! self::isMixedBox($get)),
                        TextInput::make('box_size')
                            ->label('Piezas por caja')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(12)
                            ->required(fn (Get $get): bool => Labels::value($get('type')) === ProductType::Box->value)
                            ->visible(fn (Get $get): bool => Labels::value($get('type')) === ProductType::Box->value),
                        Quetzales::input('price_cents')
                            ->label('Precio con tarjeta')
                            ->helperText(function (Get $get): string {
                                $card = Quetzales::toCents($get('price_cents'));
                                $transfer = Quetzales::toCents($get('transfer_price_cents'));
                                $hints = [];
                                if ($card) {
                                    $hints[] = 'Después de la comisión recibes ≈ '.Quetzales::format(Quetzales::netAfterCardFee($card)).' por unidad.';
                                }
                                if ($transfer) {
                                    $hints[] = 'Para cubrir la comisión sobre '.Quetzales::format($transfer).': '.Quetzales::format(Quetzales::cardPriceFor($transfer)).'.';
                                }

                                return $hints === [] ? 'Es el precio que se publica en la tienda.' : implode(' ', $hints);
                            })
                            ->live(onBlur: true)
                            ->required(),
                        Quetzales::input('transfer_price_cents')
                            ->label('Precio con transferencia')
                            ->helperText('Descuento por pagar con transferencia. Vacío = mismo precio que con tarjeta.')
                            ->live(onBlur: true)
                            ->lte('price_cents'),
                        Quetzales::input('packaging_cost_cents')
                            ->label('Costo de empaque por unidad')
                            ->helperText('Caja, bolsa, etiqueta, servilletas. El costo de los ingredientes se toma de cada sabor.')
                            ->default(0)
                            ->required(),
                        TextInput::make('sort_order')
                            ->label('Orden en el menú')
                            ->numeric()
                            ->integer()
                            ->default(0),
                        Textarea::make('description')
                            ->label('Descripción')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Publicación')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('margin_estimate')
                            ->label('Margen estimado por unidad')
                            ->state(function (?Product $record): string {
                                if (! $record) {
                                    return 'Guarda el producto para ver el margen.';
                                }
                                $cost = Costs::estimatedProductCost($record);
                                $card = Quetzales::netAfterCardFee($record->price_cents);
                                $transfer = $record->priceFor(PaymentMethod::BankTransfer);

                                return 'Costo ≈ '.Quetzales::format($cost)
                                    .' · Ganas ≈ '.Quetzales::format($transfer - $cost).' con transferencia'
                                    .' y ≈ '.Quetzales::format($card - $cost).' con tarjeta (con sabores de costo promedio).';
                            }),
                        Toggle::make('published')
                            ->label('Publicado')
                            ->helperText('Aparece en la tienda.'),
                        Toggle::make('available')
                            ->label('Disponible')
                            ->helperText('Si se apaga, se ve pero no se puede pedir.')
                            ->default(true),
                        FileUpload::make('image_path')
                            ->label('Foto')
                            ->image()
                            ->disk('catalog')
                            ->directory('images/catalogo')
                            ->visibility('public')
                            ->maxSize(4096)
                            ->helperText('JPG o PNG, máximo 4 MB. Sin foto, una caja muestra la de la categoría.'),
                    ]),
            ]);
    }
}

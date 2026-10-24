<?php

namespace App\Filament\Resources\Promotions;

use App\Enums\PromotionType;
use App\Filament\Resources\Promotions\Pages\CreatePromotion;
use App\Filament\Resources\Promotions\Pages\EditPromotion;
use App\Filament\Resources\Promotions\Pages\ListPromotions;
use App\Filament\Support\Labels;
use App\Filament\Support\Quetzales;
use App\Models\Promotion;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PromotionResource extends Resource
{
    protected static ?string $model = Promotion::class;

    protected static ?string $slug = 'promociones';

    protected static ?string $modelLabel = 'promoción';

    protected static ?string $pluralModelLabel = 'promociones';

    protected static ?string $recordTitleAttribute = 'code';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string | UnitEnum | null $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        $tz = config('rolobox.timezone');
        $type = fn (Get $get): ?string => Labels::value($get('type'));

        return $schema
            ->columns(2)
            ->components([
                Section::make('Cupón')
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Código')
                            ->helperText('Lo que escribe el cliente. Se guarda en mayúsculas.')
                            ->required()
                            ->maxLength(40)
                            ->regex('/^[A-Za-z0-9_-]+$/')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('code', mb_strtoupper(trim((string) $state))))
                            ->unique(ignoreRecord: true),
                        TextInput::make('description')
                            ->label('Descripción')
                            ->placeholder('10% de descuento en tu primera caja')
                            ->required()
                            ->maxLength(200),
                        Select::make('type')
                            ->label('Tipo de descuento')
                            ->options(Labels::promotionTypes())
                            ->default(PromotionType::Percent->value)
                            ->required()
                            ->live(),
                        TextInput::make('value')
                            ->label(fn (Get $get): string => $type($get) === PromotionType::Fixed->value ? 'Monto de descuento' : 'Porcentaje de descuento')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(fn (Get $get): ?int => $type($get) === PromotionType::Percent->value ? 100 : null)
                            ->prefix(fn (Get $get): ?string => $type($get) === PromotionType::Fixed->value ? 'Q' : null)
                            ->suffix(fn (Get $get): ?string => $type($get) === PromotionType::Percent->value ? '%' : null)
                            ->required(fn (Get $get): bool => $type($get) !== PromotionType::FreeShipping->value)
                            ->hidden(fn (Get $get): bool => $type($get) === PromotionType::FreeShipping->value)
                            // Monto fijo en centavos; porcentaje como entero.
                            ->formatStateUsing(fn ($state, ?Promotion $record) => $record?->type === PromotionType::Fixed
                                ? Quetzales::toQuetzales($state)
                                : $state)
                            ->dehydrateStateUsing(fn ($state, Get $get): int => match ($type($get)) {
                                PromotionType::Fixed->value => (int) Quetzales::toCents($state),
                                PromotionType::Percent->value => (int) $state,
                                default => 0,
                            }),
                        Select::make('applies_to')
                            ->label('Aplica a')
                            ->options([
                                'all' => 'Todo el pedido (sin envío)',
                                'boxes' => 'Solo cajas',
                            ])
                            ->default('all')
                            ->required(),
                        Quetzales::input('min_subtotal_cents')
                            ->label('Compra mínima')
                            ->default(0)
                            ->required(),
                    ]),

                Section::make('Vigencia y límites')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Empieza')
                            ->timezone($tz)
                            ->seconds(false),
                        DateTimePicker::make('ends_at')
                            ->label('Termina')
                            ->timezone($tz)
                            ->seconds(false)
                            ->after('starts_at'),
                        TextInput::make('max_redemptions')
                            ->label('Usos máximos en total')
                            ->helperText('Vacío = sin límite.')
                            ->numeric()
                            ->integer()
                            ->minValue(1),
                        Toggle::make('active')
                            ->label('Activa')
                            ->default(true),
                        Toggle::make('first_order_only')
                            ->label('Solo primera compra'),
                        Toggle::make('once_per_customer')
                            ->label('Un uso por cliente (teléfono)')
                            ->default(true),
                        Toggle::make('allow_national')
                            ->label('Aplica a envíos por paquetería')
                            ->default(true),
                    ]),

                Section::make('Banner en la página de inicio')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Toggle::make('show_banner')
                            ->label('Mostrar en el inicio')
                            ->live(),
                        TextInput::make('banner_title')
                            ->label('Título del banner')
                            ->placeholder('10% en tu primera caja')
                            ->maxLength(120)
                            ->visible(fn (Get $get): bool => (bool) $get('show_banner')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $tz = config('rolobox.timezone');

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->description(fn (Promotion $record): string => $record->description),
                TextColumn::make('value')
                    ->label('Descuento')
                    ->formatStateUsing(fn ($state, Promotion $record): string => match ($record->type) {
                        PromotionType::Percent => "{$state} %",
                        PromotionType::Fixed => Quetzales::format((int) $state),
                        PromotionType::FreeShipping => 'Envío gratis',
                    }),
                TextColumn::make('redemptions_count')
                    ->label('Usos')
                    ->counts('redemptions')
                    ->formatStateUsing(fn ($state, Promotion $record): string => $record->max_redemptions ? "{$state} / {$record->max_redemptions}" : (string) $state),
                TextColumn::make('ends_at')
                    ->label('Termina')
                    ->dateTime('d/m/Y H:i')
                    ->timezone($tz)
                    ->placeholder('Sin fecha'),
                ToggleColumn::make('show_banner')
                    ->label('Banner'),
                ToggleColumn::make('active')
                    ->label('Activa'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPromotions::route('/'),
            'create' => CreatePromotion::route('/crear'),
            'edit' => EditPromotion::route('/{record}/editar'),
        ];
    }

    /** Borrar una promoción borraría su historial de usos (y el control de "un uso por cliente"). */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}

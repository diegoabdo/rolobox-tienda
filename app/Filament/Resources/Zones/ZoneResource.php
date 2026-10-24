<?php

namespace App\Filament\Resources\Zones;

use App\Enums\DeliveryType;
use App\Filament\Resources\Zones\Pages\CreateZone;
use App\Filament\Resources\Zones\Pages\EditZone;
use App\Filament\Resources\Zones\Pages\ListZones;
use App\Filament\Support\Labels;
use App\Filament\Support\Quetzales;
use App\Models\Zone;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ZoneResource extends Resource
{
    protected static ?string $model = Zone::class;

    protected static ?string $slug = 'zonas';

    protected static ?string $modelLabel = 'zona de entrega';

    protected static ?string $pluralModelLabel = 'zonas de entrega';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string | UnitEnum | null $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->placeholder('Zona 3')
                            ->required()
                            ->maxLength(120),
                        Select::make('delivery_type')
                            ->label('Tipo de entrega')
                            ->options(Labels::deliveryTypes())
                            ->default(DeliveryType::Local->value)
                            ->required(),
                        TextInput::make('municipality')
                            ->label('Municipio')
                            ->default('Quetzaltenango')
                            ->required()
                            ->maxLength(80),
                        TextInput::make('department')
                            ->label('Departamento')
                            ->default('Quetzaltenango')
                            ->required()
                            ->maxLength(60),
                        Quetzales::input('fee_cents')
                            ->label('Envío que paga el cliente')
                            ->required(),
                        Quetzales::input('delivery_cost_cents')
                            ->label('Lo que te cuesta entregar')
                            ->helperText('Gasolina, mensajero o paquetería. Se usa para calcular ganancias.')
                            ->default(0)
                            ->required(),
                        TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric()
                            ->integer()
                            ->default(0),
                        TextInput::make('notes')
                            ->label('Nota para el cliente')
                            ->placeholder('Entregas solo por la tarde')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Toggle::make('active')
                            ->label('Activa')
                            ->helperText('Aparece como opción en el checkout.')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label('Zona')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Zone $record): string => "{$record->municipality}, {$record->department}"),
                TextColumn::make('delivery_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => Labels::deliveryType($state))
                    ->color(fn ($state): string => Labels::value($state) === DeliveryType::Local->value ? 'primary' : 'gray'),
                TextColumn::make('fee_cents')
                    ->label('Envío cobrado')
                    ->money('GTQ', divideBy: 100)
                    ->sortable(),
                TextColumn::make('delivery_cost_cents')
                    ->label('Costo de entrega')
                    ->money('GTQ', divideBy: 100)
                    ->toggleable(),
                ToggleColumn::make('active')
                    ->label('Activa'),
            ])
            ->filters([
                SelectFilter::make('delivery_type')
                    ->label('Tipo de entrega')
                    ->options(Labels::deliveryTypes()),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListZones::route('/'),
            'create' => CreateZone::route('/crear'),
            'edit' => EditZone::route('/{record}/editar'),
        ];
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}

<?php

namespace App\Filament\Resources\Flavors;

use App\Filament\Resources\Flavors\Pages\CreateFlavor;
use App\Filament\Resources\Flavors\Pages\EditFlavor;
use App\Filament\Resources\Flavors\Pages\ListFlavors;
use App\Filament\Support\Quetzales;
use App\Models\Category;
use App\Models\Flavor;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use UnitEnum;

class FlavorResource extends Resource
{
    protected static ?string $model = Flavor::class;

    protected static ?string $slug = 'sabores';

    protected static ?string $modelLabel = 'sabor';

    protected static ?string $pluralModelLabel = 'sabores';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCake;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Sabor')
                    ->description('Una variedad que se puede pedir: «Almendra» en Croissants, «Queso» en Trenzas…')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        Select::make('category_id')
                            ->label('Categoría')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->label('Nombre')->required()->maxLength(80),
                            ])
                            ->createOptionUsing(fn (array $data): int => Category::create([
                                'name' => $data['name'],
                                'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
                            ])->getKey())
                            ->helperText('¿No está? Créala aquí mismo con el signo +.'),
                        TextInput::make('name')
                            ->label('Nombre del sabor')
                            ->required()
                            ->maxLength(80)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Identificador en la URL')
                            ->required()
                            ->maxLength(80)
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        TextInput::make('sort_order')
                            ->label('Orden en el menú')
                            ->numeric()
                            ->integer()
                            ->default(0),
                        Textarea::make('description')
                            ->label('Descripción')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('allergens')
                            ->label('Alérgenos')
                            ->placeholder('Gluten, lácteos, huevo')
                            ->maxLength(200)
                            ->columnSpanFull(),
                    ]),

                Section::make('Dónde se puede pedir')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('active')
                            ->label('Activo')
                            ->helperText('Se puede pedir en la tienda.')
                            ->default(true),
                        Toggle::make('box_eligible')
                            ->label('En cajas de su categoría')
                            ->helperText('Puede ir en la caja de su categoría (ej.: caja de croissants).')
                            ->default(true),
                        Toggle::make('mix_eligible')
                            ->label('En cajas mixtas')
                            ->helperText('Puede mezclarse con otras categorías en una caja mixta.'),
                        Toggle::make('ships_nationally')
                            ->label('Apto para paquetería')
                            ->helperText('Aguanta el viaje a otros departamentos.'),
                    ]),

                Section::make('Precio y costo')
                    ->description('El precio base lo pone el producto (Croissant, Trenza completa…). Aquí solo lo que cambia por este sabor.')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        Quetzales::input('extra_cents')
                            ->label('Precio extra de este sabor')
                            ->helperText('Se suma al precio del producto cuando se pide suelto. En 0, sin extra.')
                            ->default(0),
                        Quetzales::input('cost_cents')
                            ->label('Costo por pieza')
                            ->helperText('Ingredientes de una pieza de este sabor. Se usa para calcular ganancias.')
                            ->default(0)
                            ->required(),
                    ]),

                Section::make('Foto')
                    ->columnSpan(1)
                    ->schema([
                        FileUpload::make('image_path')
                            ->hiddenLabel()
                            ->image()
                            ->disk('catalog')
                            ->directory('images/catalogo')
                            ->visibility('public')
                            ->maxSize(4096)
                            ->helperText('JPG o PNG, máximo 4 MB.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('')
                    ->disk('catalog')
                    ->circular(),
                TextColumn::make('name')
                    ->label('Sabor')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Flavor $record): ?string => $record->allergens),
                TextColumn::make('category.name')
                    ->label('Categoría')
                    ->badge()
                    ->sortable(),
                TextColumn::make('extra_cents')
                    ->label('Extra')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state): ?string => (int) $state > 0 ? '+ '.Quetzales::format((int) $state) : null),
                ToggleColumn::make('box_eligible')
                    ->label('En su caja')
                    ->tooltip('Puede ir en la caja de su categoría'),
                ToggleColumn::make('mix_eligible')
                    ->label('En caja mixta')
                    ->tooltip('Puede ir en cajas mixtas'),
                IconColumn::make('ships_nationally')
                    ->label('Paquetería')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('active')
                    ->label('Activo'),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFlavors::route('/'),
            'create' => CreateFlavor::route('/crear'),
            'edit' => EditFlavor::route('/{record}/editar'),
        ];
    }

    /** Para dejar de ofrecer un sabor se desactiva; así los carritos y pedidos viejos no se rompen. */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}

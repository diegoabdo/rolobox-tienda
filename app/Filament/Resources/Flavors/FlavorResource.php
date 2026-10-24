<?php

namespace App\Filament\Resources\Flavors;

use App\Filament\Resources\Flavors\Pages\CreateFlavor;
use App\Filament\Resources\Flavors\Pages\EditFlavor;
use App\Filament\Resources\Flavors\Pages\ListFlavors;
use App\Filament\Support\Quetzales;
use App\Models\Flavor;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCake;

    protected static string | UnitEnum | null $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Sabor')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
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
                        Quetzales::input('cost_cents')
                            ->label('Costo por rol')
                            ->helperText('Ingredientes de un rol de este sabor. Se usa para calcular ganancias.')
                            ->default(0)
                            ->required(),
                        TextInput::make('sort_order')
                            ->label('Orden en el menú')
                            ->numeric()
                            ->integer()
                            ->default(0),
                    ]),

                Section::make('Disponibilidad')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('active')
                            ->label('Activo')
                            ->helperText('Se puede elegir en la tienda.')
                            ->default(true),
                        Toggle::make('ships_nationally')
                            ->label('Apto para paquetería')
                            ->helperText('Aguanta el viaje a otros departamentos.'),
                        FileUpload::make('image_path')
                            ->label('Foto')
                            ->image()
                            ->disk('catalog')
                            ->directory('images/catalogo')
                            ->visibility('public')
                            ->maxSize(4096),
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
                TextColumn::make('cost_cents')
                    ->label('Costo por rol')
                    ->money('GTQ', divideBy: 100)
                    ->sortable(),
                IconColumn::make('ships_nationally')
                    ->label('Paquetería')
                    ->boolean(),
                ToggleColumn::make('active')
                    ->label('Activo'),
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

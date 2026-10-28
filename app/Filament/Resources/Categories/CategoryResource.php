<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use UnitEnum;

/** Las líneas de la panadería: Rolls, Croissants, Focaccias, Trenzas… Cada una es una sección del menú. */
class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $slug = 'categorias';

    protected static ?string $modelLabel = 'categoría';

    protected static ?string $pluralModelLabel = 'categorías';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Categoría')
                    ->description('Una línea de la panadería. Es una sección del menú en la tienda.')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->placeholder('Croissants')
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
                            ->label('Descripción corta')
                            ->helperText('Aparece bajo el nombre de la categoría en el menú.')
                            ->rows(2)
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('sort_order')
                            ->label('Orden en el menú')
                            ->numeric()
                            ->integer()
                            ->default(0),
                    ]),

                Section::make('En la tienda')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('active')
                            ->label('Visible')
                            ->helperText('Si se apaga, la categoría y sus sabores desaparecen de la tienda.')
                            ->default(true),
                        FileUpload::make('image_path')
                            ->label('Foto')
                            ->image()
                            ->disk('catalog')
                            ->directory('images/catalogo')
                            ->visibility('public')
                            ->maxSize(4096)
                            ->helperText('Se ve en un círculo en la portada. Sin foto, se muestra un círculo de color con el nombre.'),
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
                    ->label('Categoría')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Category $record): ?string => $record->description),
                TextColumn::make('flavors_count')
                    ->label('Sabores')
                    ->counts('flavors'),
                TextColumn::make('products_count')
                    ->label('Productos')
                    ->counts('products'),
                ToggleColumn::make('active')
                    ->label('Visible'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/crear'),
            'edit' => EditCategory::route('/{record}/editar'),
        ];
    }

    /** Para dejar de ofrecer una categoría se oculta; así los sabores, productos y pedidos viejos no se rompen. */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}

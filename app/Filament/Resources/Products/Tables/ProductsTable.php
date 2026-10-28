<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Support\Labels;
use App\Models\Product;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('')
                    ->disk('catalog')
                    ->square(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('category.name')
                    ->label('Categoría')
                    ->badge()
                    ->placeholder('Mixta')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Se vende')
                    ->badge()
                    ->formatStateUsing(fn ($state, Product $record): string => $record->isBox()
                        ? 'Caja '.($record->isMixedBox() ? 'mixta' : 'de su categoría')
                        : Labels::productType($state)),
                TextColumn::make('box_size')
                    ->label('Piezas')
                    ->placeholder('1'),
                TextColumn::make('price_cents')
                    ->label('Con tarjeta')
                    ->money('GTQ', divideBy: 100)
                    ->sortable(),
                TextColumn::make('transfer_price_cents')
                    ->label('Con transferencia')
                    ->money('GTQ', divideBy: 100)
                    ->placeholder('Igual'),
                ToggleColumn::make('published')
                    ->label('Publicado'),
                ToggleColumn::make('available')
                    ->label('Disponible'),
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
}

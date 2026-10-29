<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Flavor;
use App\Models\Product;

/** Lo que la tienda muestra del catálogo: categorías activas con sus sabores y productos publicados. */
class Catalog
{
    /**
     * @return array{categories: list<array<string, mixed>>, flavors: list<array<string, mixed>>, products: list<array<string, mixed>>}
     */
    public static function storefront(): array
    {
        $categories = Category::active()->get();
        $categoryIds = $categories->pluck('id')->all();

        $flavors = Flavor::active()->whereIn('category_id', $categoryIds)->get();

        // Las cajas mixtas no tienen categoría propia; el resto debe pertenecer a una categoría activa.
        $products = Product::published()->get()
            ->filter(fn (Product $product): bool => $product->isMixedBox() || in_array($product->category_id, $categoryIds, true));

        return [
            'categories' => $categories->map->toStorefront()->values()->all(),
            'flavors' => $flavors->map->toStorefront()->values()->all(),
            'products' => $products->map->toStorefront()->values()->all(),
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryType;
use App\Enums\ProductType;
use App\Models\Flavor;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Zone;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function home(): Response
    {
        $products = Product::published()->get();
        $zones = Zone::active()->get();

        return Inertia::render('Home', [
            'products' => $products->map->toStorefront()->values(),
            'flavors' => Flavor::active()->get()->map->toStorefront()->values(),
            'singleProductId' => $products->firstWhere('type', ProductType::Single)?->id,
            'promotion' => Promotion::running()->where('show_banner', true)->latest('id')->first()?->toBanner(),
            'coverage' => [
                'local' => $zones->where('delivery_type', DeliveryType::Local)->map->toStorefront()->values(),
                'national' => $zones->where('delivery_type', DeliveryType::National)->map->toStorefront()->values(),
            ],
        ]);
    }

    public function box(Request $request): Response
    {
        $box = Product::published()->where('type', ProductType::Box)->firstOrFail();

        return Inertia::render('ArmarCaja', [
            'box' => $box->toStorefront(),
            'flavors' => Flavor::active()->get()->map->toStorefront()->values(),
            'preselect' => $request->string('sabor')->limit(80)->toString() ?: null,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryType;
use App\Enums\ProductType;
use App\Models\Flavor;
use App\Models\Promotion;
use App\Models\Zone;
use App\Services\Catalog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function home(): Response
    {
        $zones = Zone::active()->get();

        return Inertia::render('Home', [
            ...Catalog::storefront(),
            'promotion' => Promotion::running()->where('show_banner', true)->latest('id')->first()?->toBanner(),
            'coverage' => [
                'local' => $zones->where('delivery_type', DeliveryType::Local)->map->toStorefront()->values(),
                'national' => $zones->where('delivery_type', DeliveryType::National)->map->toStorefront()->values(),
            ],
        ]);
    }

    public function about(): Response
    {
        return Inertia::render('Nosotros');
    }

    /**
     * Armar caja. `caja` elige la caja por su identificador; `sabor` precarga un sabor
     * y, si no se dice la caja, abre la primera que lo acepta.
     */
    public function box(Request $request): Response
    {
        $catalog = Catalog::storefront();
        $boxes = collect($catalog['products'])->where('type', ProductType::Box->value)->values();
        abort_if($boxes->isEmpty(), 404);

        $preselect = $request->string('sabor')->limit(80)->toString() ?: null;
        $flavor = $preselect ? Flavor::where('slug', $preselect)->first() : null;

        $selected = $boxes->firstWhere('slug', $request->string('caja')->limit(120)->toString())
            ?? ($flavor ? $boxes->first(fn (array $box): bool => $box['mixed'] ? $flavor->mix_eligible : ($box['categoryId'] === $flavor->category_id && $flavor->box_eligible)) : null)
            ?? $boxes->first();

        return Inertia::render('ArmarCaja', [
            'categories' => $catalog['categories'],
            'flavors' => $catalog['flavors'],
            'boxes' => $boxes->all(),
            'selectedBox' => $selected['slug'],
            'preselect' => $preselect,
        ]);
    }
}

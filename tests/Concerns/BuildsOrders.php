<?php

namespace Tests\Concerns;

use App\Models\Flavor;
use App\Models\Product;
use App\Models\Zone;
use App\Support\DeliverySchedule;
use Database\Seeders\CatalogSeeder;
use Illuminate\Support\Str;

trait BuildsOrders
{
    protected function seedCatalog(): void
    {
        $this->seed(CatalogSeeder::class);
    }

    protected function box(): Product
    {
        return Product::where('slug', 'caja-de-seis')->firstOrFail();
    }

    protected function single(): Product
    {
        return Product::where('slug', 'rol-suelto')->firstOrFail();
    }

    protected function flavor(string $slug): Flavor
    {
        return Flavor::where('slug', $slug)->firstOrFail();
    }

    protected function addBoxToCart(): void
    {
        $this->post('/carrito', [
            'product_id' => $this->box()->id,
            'flavors' => [
                $this->flavor('canela-clasica')->id => 3,
                $this->flavor('chocolate')->id => 3,
            ],
        ])->assertSessionHasNoErrors();
    }

    /** @return array<string, mixed> */
    protected function checkoutData(array $overrides = []): array
    {
        $zone = Zone::where('name', 'Zona 3')->firstOrFail();

        return array_merge([
            'idempotency_key' => (string) Str::uuid(),
            'name' => 'Ana López',
            'phone' => '5555 1234',
            'email' => '',
            'nit' => '',
            'zone_id' => $zone->id,
            'address' => '12 avenida 4-30, zona 3',
            'address_reference' => 'Portón negro',
            'delivery_date' => DeliverySchedule::availableDateStrings()[0],
            'delivery_slot' => '11-13',
            'notes' => '',
            'promotion_code' => '',
            'payment_method' => 'card',
            'terms' => true,
        ], $overrides);
    }
}

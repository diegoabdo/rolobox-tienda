<?php

namespace Database\Seeders;

use App\Enums\DeliveryType;
use App\Enums\ProductType;
use App\Enums\PromotionType;
use App\Models\Flavor;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Zone;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial. Las tarifas de envío y la promoción son de ejemplo: ajústalas en el panel.
 *
 * Precios: el publicado (price_cents) es el de tarjeta y ya cubre la comisión de Recurrente
 * (4.5% + Q2); transfer_price_cents es el precio con descuento por pagar con transferencia.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $flavors = [
            ['Canela clásica', 'canela-clasica', 'Masa suave, canela y glaseado de queso crema.', 'Gluten, lácteos, huevo', 'images/canela.jpg', true],
            ['Chocolate', 'chocolate', 'Ganache de chocolate con trozos de chocolate.', 'Gluten, lácteos, huevo', 'images/chocolate.jpg', true],
            ['Calabaza y pepitas', 'calabaza-y-pepitas', 'Glaseado especiado, crumble y pepitas tostadas.', 'Gluten, lácteos, huevo, semillas', 'images/calabaza.jpg', false],
            ['Cacao y crema', 'cacao-y-crema', 'Glaseado de crema con cacao espolvoreado.', 'Gluten, lácteos, huevo', 'images/cacao.jpg', false],
            ['Galleta caramelizada', 'galleta-caramelizada', 'Crema de galleta y crumble caramelizado.', 'Gluten, lácteos, huevo, soya', 'images/galleta.jpg', true],
            ['Nuez y caramelo', 'nuez-y-caramelo', 'Caramelo, glaseado y nueces tostadas.', 'Gluten, lácteos, huevo, nueces', 'images/nuez.jpg', false],
        ];

        foreach ($flavors as $i => [$name, $slug, $description, $allergens, $image, $ships]) {
            Flavor::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $description,
                'allergens' => $allergens,
                'image_path' => $image,
                'ships_nationally' => $ships,
                'active' => true,
                'sort_order' => $i,
            ]);
        }

        Product::updateOrCreate(['slug' => 'caja-de-seis'], [
            'name' => 'Caja de seis roles',
            'type' => ProductType::Box,
            'description' => 'Seis roles a tu elección para compartir, regalar o acompañar el café.',
            'box_size' => 6,
            'price_cents' => 11800,
            'transfer_price_cents' => 11000,
            'image_path' => 'images/caja.jpg',
            'published' => true,
            'available' => true,
            'sort_order' => 0,
        ]);

        Product::updateOrCreate(['slug' => 'rol-suelto'], [
            'name' => 'Rol suelto',
            'type' => ProductType::Single,
            'description' => 'Un rol del sabor que elijas.',
            'box_size' => null,
            'price_cents' => 2300,
            'transfer_price_cents' => 2000,
            'image_path' => null,
            'published' => true,
            'available' => true,
            'sort_order' => 1,
        ]);

        // Zonas de reparto propio en Quetzaltenango. Tarifas de EJEMPLO.
        $local = [
            ['Zona 1', 1500], ['Zona 2', 1500], ['Zona 3', 1500], ['Zona 4', 2000],
            ['Zona 5', 2000], ['Zona 7', 2000], ['Zona 8', 2500], ['Zona 9', 2500],
        ];
        foreach ($local as $i => [$name, $fee]) {
            Zone::updateOrCreate(
                ['name' => $name, 'municipality' => 'Quetzaltenango', 'delivery_type' => DeliveryType::Local],
                ['department' => 'Quetzaltenango', 'fee_cents' => $fee, 'active' => true, 'sort_order' => $i],
            );
        }

        // Envío nacional: queda INACTIVO hasta definir sabores aptos para viaje y paquetería.
        $national = [
            ['Ciudad de Guatemala', 'Guatemala', 'Guatemala'],
            ['Mixco', 'Guatemala', 'Mixco'],
            ['Antigua Guatemala', 'Sacatepéquez', 'Antigua Guatemala'],
        ];
        foreach ($national as $i => [$name, $department, $municipality]) {
            Zone::updateOrCreate(
                ['name' => $name, 'municipality' => $municipality, 'delivery_type' => DeliveryType::National],
                ['department' => $department, 'fee_cents' => 4500, 'active' => false, 'sort_order' => 100 + $i],
            );
        }

        Promotion::updateOrCreate(['code' => 'BIENVENIDA'], [
            'description' => '10% de descuento en tu primera caja',
            'type' => PromotionType::Percent,
            'value' => 10,
            'min_subtotal_cents' => 0,
            'first_order_only' => true,
            'once_per_customer' => true,
            'applies_to' => 'boxes',
            'allow_national' => true,
            'show_banner' => true,
            'banner_title' => '10% en tu primera caja',
            'active' => true,
        ]);
    }
}

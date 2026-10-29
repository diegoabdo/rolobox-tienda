<?php

namespace Database\Seeders;

use App\Enums\BoxScope;
use App\Enums\DeliveryType;
use App\Enums\ProductType;
use App\Enums\PromotionType;
use App\Models\Category;
use App\Models\Flavor;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Zone;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial. Las tarifas de envío, los sabores y precios de croissants, focaccias y trenzas
 * y la promoción son de ejemplo: ajústalos en el panel.
 *
 * Precios: el publicado (price_cents) es el de tarjeta y ya cubre la comisión de Recurrente
 * (4.5% + Q2); transfer_price_cents es el precio con descuento por pagar con transferencia.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $rolls = Category::updateOrCreate(['slug' => 'rolls-de-canela'], [
            'name' => 'Rolls de canela',
            'description' => 'Masa suave, canela y glaseados. Pídelos sueltos o en caja de seis.',
            'image_path' => 'images/caja.jpg',
            'active' => true,
            'sort_order' => 0,
        ]);

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
                'category_id' => $rolls->id,
                'box_eligible' => true,
                'mix_eligible' => true,
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
            'category_id' => $rolls->id,
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
            'category_id' => $rolls->id,
            'name' => 'Rol',
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

        $this->bakery();

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

    /**
     * Croissants, focaccias, trenzas y una caja mixta. Nombres, sabores y precios son de EJEMPLO:
     * cámbialos, desactívalos o agrega los tuyos en el panel (Catálogo).
     */
    private function bakery(): void
    {
        $categories = [];
        $definitions = [
            ['croissants', 'Croissants', 'Hojaldrados, con relleno dulce o salado.', 1],
            ['focaccias', 'Focaccias', 'Masa alta y aceitosa, con hierbas y verduras.', 2],
            ['trenzas', 'Trenzas', 'Trenzas de masa suave, completas, por porción o pequeñas.', 3],
        ];
        foreach ($definitions as [$slug, $name, $description, $order]) {
            $categories[$slug] = Category::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $description,
                'active' => true,
                'sort_order' => $order,
            ]);
        }

        // [categoría, nombre, slug, descripción, alérgenos, extra en centavos, caja de su categoría, caja mixta]
        $items = [
            ['croissants', 'Mantequilla', 'croissant-mantequilla', 'El clásico hojaldrado, dorado y crujiente.', 'Gluten, lácteos, huevo', 0, true, true],
            ['croissants', 'Almendra', 'croissant-almendra', 'Relleno de crema de almendra y almendras tostadas.', 'Gluten, lácteos, huevo, nueces', 300, true, true],
            ['croissants', 'Chocolate', 'croissant-chocolate', 'Relleno de chocolate semiamargo.', 'Gluten, lácteos, huevo, soya', 200, true, true],
            ['croissants', 'Jamón y queso', 'croissant-jamon-y-queso', 'Jamón y queso derretido.', 'Gluten, lácteos, huevo', 400, true, false],
            ['focaccias', 'Romero y sal', 'focaccia-romero-y-sal', 'Aceite de oliva, romero y sal en escamas.', 'Gluten', 0, true, false],
            ['focaccias', 'Tomate y albahaca', 'focaccia-tomate-y-albahaca', 'Tomate cherry, albahaca y aceite de oliva.', 'Gluten', 500, true, false],
            ['trenzas', 'Queso', 'trenza-queso', 'Trenza rellena de queso crema.', 'Gluten, lácteos, huevo', 0, true, false],
            ['trenzas', 'Canela y azúcar', 'trenza-canela-y-azucar', 'Trenza con canela y azúcar morena.', 'Gluten, lácteos, huevo', 0, true, false],
        ];

        foreach ($items as $i => [$category, $name, $slug, $description, $allergens, $extra, $box, $mixed]) {
            Flavor::updateOrCreate(['slug' => $slug], [
                'category_id' => $categories[$category]->id,
                'name' => $name,
                'description' => $description,
                'allergens' => $allergens,
                'extra_cents' => $extra,
                'box_eligible' => $box,
                'mix_eligible' => $mixed,
                'ships_nationally' => false,
                'active' => true,
                'sort_order' => 10 + $i,
            ]);
        }

        // [categoría, nombre, slug, tipo, piezas por caja, con tarjeta, con transferencia, descripción]
        $products = [
            ['croissants', 'Croissant', 'croissant', ProductType::Single, null, 1800, 1650, 'Un croissant del sabor que elijas.'],
            ['croissants', 'Caja de cuatro croissants', 'caja-de-cuatro-croissants', ProductType::Box, 4, 6800, 6300, 'Cuatro croissants a tu elección.'],
            ['focaccias', 'Focaccia', 'focaccia', ProductType::Single, null, 4500, 4200, 'Una focaccia entera del sabor que elijas.'],
            ['trenzas', 'Trenza completa', 'trenza-completa', ProductType::Single, null, 6500, 6000, 'Trenza entera, para compartir.'],
            ['trenzas', 'Porción de trenza', 'porcion-de-trenza', ProductType::Single, null, 1500, 1400, 'Una porción de trenza.'],
            ['trenzas', 'Trenza pequeña', 'trenza-pequena', ProductType::Single, null, 3200, 3000, 'Trenza pequeña, para una o dos personas.'],
        ];

        foreach ($products as $i => [$category, $name, $slug, $type, $size, $card, $transfer, $description]) {
            Product::updateOrCreate(['slug' => $slug], [
                'category_id' => $categories[$category]->id,
                'name' => $name,
                'type' => $type,
                'box_scope' => BoxScope::Category,
                'description' => $description,
                'box_size' => $size,
                'price_cents' => $card,
                'transfer_price_cents' => $transfer,
                'published' => true,
                'available' => true,
                'sort_order' => 10 + $i,
            ]);
        }

        // La caja mixta queda lista pero DESACTIVADA: por ahora solo hay cajas de un mismo producto y sus variantes.
        // Para activarla: Panel → Productos → «Caja mixta de seis» → Publicado. Con firstOrCreate, volver a correr el seeder no la reactiva.
        Product::firstOrCreate(['slug' => 'caja-mixta-de-seis'], [
            'category_id' => null,
            'name' => 'Caja mixta de seis',
            'type' => ProductType::Box,
            'box_scope' => BoxScope::Mixed,
            'description' => 'Seis piezas a tu elección, mezclando rolls y croissants.',
            'box_size' => 6,
            'price_cents' => 12800,
            'transfer_price_cents' => 11900,
            'published' => false,
            'available' => true,
            'sort_order' => 20,
        ]);
    }
}

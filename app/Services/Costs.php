<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Flavor;
use App\Models\Product;

/** Costos de producción y de cobro, para calcular la ganancia real de cada venta. */
class Costs
{
    /**
     * Costo de una línea del carrito: (empaque + costo de cada rol según su sabor) × cantidad.
     *
     * @param  list<array{id: int, count: int}>  $flavors
     * @param  array<int, int>  $flavorCosts  flavor_id => costo por rol en centavos
     */
    public static function line(Product $product, array $flavors, int $quantity, array $flavorCosts): int
    {
        $perUnit = (int) $product->packaging_cost_cents;
        foreach ($flavors as $flavor) {
            $perUnit += ((int) $flavor['count']) * ($flavorCosts[(int) $flavor['id']] ?? 0);
        }

        return $perUnit * $quantity;
    }

    /** Costo por rol de cada sabor, para no consultar la base por cada línea. @return array<int, int> */
    public static function flavorCosts(array $flavorIds): array
    {
        return Flavor::whereIn('id', $flavorIds)->pluck('cost_cents', 'id')->map(fn ($c) => (int) $c)->all();
    }

    /**
     * Comisión estimada del procesador. Con tarjeta en línea usa la tarifa de config/rolobox.php
     * (4.5% + Q2); con tarjeta en persona (POS), la del POS. Transferencias y efectivo no pagan comisión.
     */
    public static function paymentFee(PaymentMethod|string|null $method, int $totalCents, bool $inPerson = false): int
    {
        $method = $method instanceof PaymentMethod ? $method : PaymentMethod::tryFrom((string) $method);

        if ($method !== PaymentMethod::Card) {
            return 0;
        }

        $key = $inPerson ? 'rolobox.payments.pos_fee' : 'rolobox.payments.card_fee';
        $percent = (float) config("{$key}.percent", 4.5);
        $fixed = (int) config("{$key}.fixed_cents", $inPerson ? 0 : 200);

        return (int) round($totalCents * $percent / 100) + $fixed;
    }

    /** Costo estimado de un producto usando el costo promedio de los sabores activos (para el panel). */
    public static function estimatedProductCost(Product $product): int
    {
        $average = (int) round((float) Flavor::where('active', true)->avg('cost_cents'));
        $rolls = $product->isBox() ? (int) $product->box_size : 1;

        return (int) $product->packaging_cost_cents + $rolls * $average;
    }
}

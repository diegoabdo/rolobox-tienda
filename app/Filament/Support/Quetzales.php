<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;

/** Montos: la base guarda centavos; el panel muestra y recibe quetzales. */
class Quetzales
{
    /** Campo en quetzales (150.00) que se guarda en centavos (15000). */
    public static function input(string $name): TextInput
    {
        return TextInput::make($name)
            ->numeric()
            ->minValue(0)
            ->step(0.01)
            ->prefix('Q')
            ->formatStateUsing(fn ($state) => self::toQuetzales($state))
            ->dehydrateStateUsing(fn ($state) => self::toCents($state));
    }

    public static function toQuetzales(mixed $cents): ?string
    {
        return $cents === null || $cents === '' ? null : number_format(((int) $cents) / 100, 2, '.', '');
    }

    public static function toCents(mixed $quetzales): ?int
    {
        return $quetzales === null || $quetzales === '' ? null : (int) round(((float) $quetzales) * 100);
    }

    /**
     * Precio con tarjeta para recibir lo mismo que con transferencia en una venta de una unidad,
     * después de la comisión del procesador (por defecto 4.5% + Q2). Se redondea al quetzal hacia arriba.
     */
    public static function cardPriceFor(int $netCents): int
    {
        $percent = (float) config('rolobox.payments.card_fee.percent', 4.5);
        $fixed = (int) config('rolobox.payments.card_fee.fixed_cents', 200);

        return (int) (ceil(($netCents + $fixed) / (1 - $percent / 100) / 100) * 100);
    }

    /** Lo que queda de un cobro con tarjeta después de la comisión del procesador. */
    public static function netAfterCardFee(int $cents): int
    {
        $percent = (float) config('rolobox.payments.card_fee.percent', 4.5);
        $fixed = (int) config('rolobox.payments.card_fee.fixed_cents', 200);

        return (int) round($cents * (1 - $percent / 100) - $fixed);
    }

    public static function format(?int $cents): string
    {
        return $cents === null ? '—' : 'Q '.number_format($cents / 100, 2);
    }
}

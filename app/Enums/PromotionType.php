<?php

namespace App\Enums;

enum PromotionType: string
{
    /** Porcentaje sobre el subtotal de productos (value = porcentaje entero, 1–100). */
    case Percent = 'percent';

    /** Monto fijo en centavos (value = centavos). */
    case Fixed = 'fixed';

    /** El envío no se cobra (value se ignora). */
    case FreeShipping = 'free_shipping';
}

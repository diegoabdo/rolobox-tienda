<?php

namespace App\Enums;

enum DeliveryType: string
{
    /** Reparto propio de Twist & Rolls (Xela). */
    case Local = 'local';

    /** Envío por paquetería al resto del país. */
    case National = 'national';

    public function label(): string
    {
        return match ($this) {
            self::Local => 'Reparto Twist & Rolls',
            self::National => 'Envío por paquetería',
        };
    }
}

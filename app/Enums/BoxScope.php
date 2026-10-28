<?php

namespace App\Enums;

enum BoxScope: string
{
    /** La caja lleva solo sabores de su propia categoría (caja de croissants, de roles…). */
    case Category = 'category';

    /** Caja mixta: mezcla sabores de varias categorías que estén marcados para cajas mixtas. */
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Category => 'De una sola categoría',
            self::Mixed => 'Mixta (varias categorías)',
        };
    }
}

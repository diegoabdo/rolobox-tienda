<?php

namespace App\Enums;

enum SalesChannel: string
{
    case Online = 'online';

    case InPerson = 'in_person';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'En línea',
            self::InPerson => 'En persona',
        };
    }
}

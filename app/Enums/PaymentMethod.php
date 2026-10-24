<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Card = 'card';

    case BankTransfer = 'bank_transfer';

    /** Solo en ventas en persona. */
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::Card => 'Tarjeta',
            self::BankTransfer => 'Transferencia bancaria',
            self::Cash => 'Efectivo',
        };
    }

    /** Las que se pueden elegir en la tienda en línea. @return list<self> */
    public static function online(): array
    {
        return [self::Card, self::BankTransfer];
    }
}

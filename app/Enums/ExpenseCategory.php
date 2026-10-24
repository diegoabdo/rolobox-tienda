<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Ingredients = 'ingredients';
    case Packaging = 'packaging';
    case Utilities = 'utilities';
    case Delivery = 'delivery';
    case Marketing = 'marketing';
    case Equipment = 'equipment';
    case Wages = 'wages';
    case Fees = 'fees';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Ingredients => 'Ingredientes',
            self::Packaging => 'Empaques',
            self::Utilities => 'Gas, luz y agua',
            self::Delivery => 'Transporte y entregas',
            self::Marketing => 'Publicidad',
            self::Equipment => 'Equipo y utensilios',
            self::Wages => 'Sueldos y ayudantes',
            self::Fees => 'Comisiones y servicios',
            self::Other => 'Otros',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}

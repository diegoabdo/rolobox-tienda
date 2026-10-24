<?php

namespace App\Support;

class Phone
{
    /**
     * Normaliza un teléfono de Guatemala a 502 + 8 dígitos.
     * Devuelve null si no es un número válido.
     */
    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if (strlen($digits) === 11 && str_starts_with($digits, '502')) {
            $digits = substr($digits, 3);
        }

        // Los números de Guatemala tienen 8 dígitos y empiezan con 2–7.
        if (! preg_match('/^[2-7]\d{7}$/', $digits)) {
            return null;
        }

        return '502'.$digits;
    }

    public static function display(string $normalized): string
    {
        $local = str_starts_with($normalized, '502') ? substr($normalized, 3) : $normalized;

        return substr($local, 0, 4).' '.substr($local, 4);
    }
}

<?php

namespace App\Services\Payments;

/**
 * Verificación de firmas de webhooks con el esquema de Svix (el que usa Recurrente).
 * Contenido firmado: "{svix-id}.{svix-timestamp}.{cuerpo crudo}" con HMAC-SHA256.
 */
class SvixSignature
{
    public static function verify(
        string $payload,
        ?string $id,
        ?string $timestamp,
        ?string $signatureHeader,
        ?string $secret,
        int $toleranceSeconds = 300,
        ?int $now = null,
    ): bool {
        if (! $id || ! $timestamp || ! $signatureHeader || ! $secret || ! ctype_digit($timestamp)) {
            return false;
        }

        $now ??= time();
        if (abs($now - (int) $timestamp) > $toleranceSeconds) {
            return false;
        }

        $key = base64_decode(str_starts_with($secret, 'whsec_') ? substr($secret, 6) : $secret, true);
        if ($key === false || $key === '') {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', "{$id}.{$timestamp}.{$payload}", $key, true));

        // El encabezado puede traer varias firmas separadas por espacios: "v1,abc v1,def".
        foreach (preg_split('/\s+/', trim($signatureHeader)) ?: [] as $candidate) {
            [$version, $signature] = array_pad(explode(',', $candidate, 2), 2, '');
            if ($version === 'v1' && hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}

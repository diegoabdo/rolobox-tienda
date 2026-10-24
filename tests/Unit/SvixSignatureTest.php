<?php

namespace Tests\Unit;

use App\Services\Payments\SvixSignature;
use PHPUnit\Framework\TestCase;

class SvixSignatureTest extends TestCase
{
    private const SECRET = 'whsec_dGVzdC1zZWNyZXQtcm9sb2JveA==';

    private function sign(string $id, int $ts, string $body): string
    {
        $key = base64_decode(substr(self::SECRET, 6));

        return 'v1,'.base64_encode(hash_hmac('sha256', "{$id}.{$ts}.{$body}", $key, true));
    }

    public function test_valid_signature_passes(): void
    {
        $body = '{"event_type":"intent.succeeded"}';
        $ts = 1_800_000_000;

        $this->assertTrue(SvixSignature::verify($body, 'msg_1', (string) $ts, $this->sign('msg_1', $ts, $body), self::SECRET, 300, $ts));
        // Varias firmas en el encabezado (rotación de secreto).
        $this->assertTrue(SvixSignature::verify($body, 'msg_1', (string) $ts, 'v1,otra '.$this->sign('msg_1', $ts, $body), self::SECRET, 300, $ts));
    }

    public function test_tampered_body_old_timestamp_or_wrong_secret_fail(): void
    {
        $body = '{"event_type":"intent.succeeded"}';
        $ts = 1_800_000_000;
        $signature = $this->sign('msg_1', $ts, $body);

        $this->assertFalse(SvixSignature::verify($body.' ', 'msg_1', (string) $ts, $signature, self::SECRET, 300, $ts));
        $this->assertFalse(SvixSignature::verify($body, 'msg_1', (string) $ts, $signature, self::SECRET, 300, $ts + 301));
        $this->assertFalse(SvixSignature::verify($body, 'msg_1', (string) $ts, $signature, 'whsec_b3RybyBzZWNyZXRv', 300, $ts));
        $this->assertFalse(SvixSignature::verify($body, null, (string) $ts, $signature, self::SECRET, 300, $ts));
    }
}

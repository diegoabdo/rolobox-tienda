<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Services\Payments\OrderPayments;
use App\Services\Payments\PaymentGatewayException;
use App\Services\Payments\SvixSignature;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RecurrenteWebhookController extends Controller
{
    /**
     * Eventos que cambian el estado de un cobro (formato unificado y formato anterior de Recurrente).
     * "pending" llega cuando el cliente inicia una transferencia; "succeeded", cuando se acredita.
     */
    private const SYNC_EVENTS = [
        'intent.succeeded',
        'intent.pending',
        'payment_intent.succeeded',
        'bank_transfer_intent.succeeded',
        'bank_transfer_intent.pending',
    ];

    public function __invoke(Request $request, OrderPayments $payments): JsonResponse
    {
        $raw = $request->getContent();
        $eventId = $request->header('svix-id');

        $valid = SvixSignature::verify(
            payload: $raw,
            id: $eventId,
            timestamp: $request->header('svix-timestamp'),
            signatureHeader: $request->header('svix-signature'),
            secret: config('rolobox.payments.recurrente.webhook_secret'),
            toleranceSeconds: (int) config('rolobox.payments.recurrente.webhook_tolerance', 300),
        );

        if (! $valid) {
            return response()->json(['error' => 'invalid signature'], 400);
        }

        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            return response()->json(['error' => 'invalid payload'], 400);
        }

        $eventType = (string) ($payload['event_type'] ?? $payload['type'] ?? 'unknown');

        try {
            $event = PaymentEvent::firstOrCreate(
                ['provider' => 'recurrente', 'event_id' => $eventId],
                ['event_type' => substr($eventType, 0, 80)],
            );
        } catch (UniqueConstraintViolationException) {
            // Dos entregas simultáneas del mismo evento: la otra lo procesa.
            return response()->json(['ok' => true]);
        }

        if ($event->processed_at) {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        if (in_array($eventType, self::SYNC_EVENTS, true)) {
            $checkoutId = data_get($payload, 'checkout.id') ?? data_get($payload, 'checkout_id');
            $payment = is_string($checkoutId)
                ? Payment::where('provider', 'recurrente')->where('provider_checkout_id', $checkoutId)->first()
                : null;

            if ($payment) {
                try {
                    // No se confía en el contenido del webhook: se consulta el checkout a Recurrente.
                    $payments->syncFromGateway($payment);
                } catch (PaymentGatewayException $e) {
                    Log::error('No se pudo confirmar un pago del webhook', ['event' => $eventId, 'error' => $e->getMessage()]);

                    // 500 = Recurrente reintenta más tarde.
                    return response()->json(['error' => 'retry'], 500);
                }
                $event->payment_id = $payment->id;
            } else {
                Log::notice('Webhook de pago sin checkout conocido', ['event' => $eventId]);
            }
        }

        $event->processed_at = now();
        $event->save();

        return response()->json(['ok' => true]);
    }
}

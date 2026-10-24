<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Services\Payments\CheckoutSession;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\RemoteCheckout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOrders;
use Tests\TestCase;

class RecurrenteWebhookTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;

    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();

        // Procesador de prueba que siempre responde "pagado" con el monto del pedido.
        $this->app->instance(PaymentGateway::class, new class implements PaymentGateway
        {
            public function name(): string
            {
                return 'recurrente';
            }

            public function createCheckout(Order $order, Payment $payment, string $successUrl, string $cancelUrl): CheckoutSession
            {
                return new CheckoutSession('ch_test_'.$payment->id, 'https://pago.example/ch_test_'.$payment->id, now()->addHour());
            }

            public function fetchCheckout(Payment $payment): RemoteCheckout
            {
                return new RemoteCheckout((string) $payment->provider_checkout_id, true, $payment->amount_cents, 'pa_123', 'Visa', '4242');
            }
        });

        $this->addBoxToCart();
        $this->post('/checkout', $this->checkoutData())->assertRedirect('https://pago.example/ch_test_1');
        $this->payment = Payment::sole();
    }

    private function send(string $eventId, array $payload, ?string $secret = null): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($payload);
        $ts = time();
        $key = base64_decode(substr($secret ?? config('rolobox.payments.recurrente.webhook_secret'), 6));
        $signature = 'v1,'.base64_encode(hash_hmac('sha256', "{$eventId}.{$ts}.{$body}", $key, true));

        return $this->call('POST', '/webhooks/recurrente', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_SVIX_ID' => $eventId,
            'HTTP_SVIX_TIMESTAMP' => (string) $ts,
            'HTTP_SVIX_SIGNATURE' => $signature,
        ], $body);
    }

    public function test_rejects_unsigned_requests(): void
    {
        $this->postJson('/webhooks/recurrente', ['event_type' => 'intent.succeeded'])->assertStatus(400);
        $this->send('msg_bad', ['event_type' => 'intent.succeeded'], 'whsec_b3RybyBzZWNyZXRv')->assertStatus(400);

        $this->assertSame(OrderStatus::PendingPayment, $this->payment->order->fresh()->status);
    }

    public function test_success_event_marks_the_order_paid_once(): void
    {
        $payload = ['event_type' => 'intent.succeeded', 'status' => 'succeeded', 'checkout' => ['id' => 'ch_test_1']];

        $this->send('msg_1', $payload)->assertOk();
        $this->send('msg_1', $payload)->assertOk()->assertJson(['duplicate' => true]);

        $order = $this->payment->order->fresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame('4242', $this->payment->fresh()->card_last4);
        $this->assertSame(1, PaymentEvent::count());
    }

    public function test_legacy_event_name_also_works(): void
    {
        $this->send('msg_2', ['event_type' => 'payment_intent.succeeded', 'checkout' => ['id' => 'ch_test_1']])->assertOk();

        $this->assertTrue($this->payment->order->fresh()->isPaid());
    }

    public function test_other_events_are_recorded_without_changes(): void
    {
        $this->send('msg_3', ['event_type' => 'intent.failed', 'checkout' => ['id' => 'ch_test_1']])->assertOk();

        $this->assertFalse($this->payment->order->fresh()->isPaid());
        $this->assertNotNull(PaymentEvent::sole()->processed_at);
    }
}

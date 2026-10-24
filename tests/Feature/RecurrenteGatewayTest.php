<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\RecurrenteGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\BuildsOrders;
use Tests\TestCase;

class RecurrenteGatewayTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;

    private function orderPaidWith(string $method): Order
    {
        $this->seedCatalog();
        $this->addBoxToCart();
        $this->post('/checkout', $this->checkoutData(['payment_method' => $method]))->assertRedirect();

        return Order::sole();
    }

    private function gateway(): RecurrenteGateway
    {
        return new RecurrenteGateway('https://app.recurrente.com/api', 'sk_test_123');
    }

    public function test_checkout_only_offers_the_method_the_customer_chose(): void
    {
        $order = $this->orderPaidWith('bank_transfer');
        Http::fake(['*/checkouts' => Http::response(['id' => 'ch_1', 'checkout_url' => 'https://pago.example/ch_1'], 201)]);

        $session = $this->gateway()->createCheckout($order, Payment::sole(), 'https://ok', 'https://cancel');

        Http::assertSent(fn (Request $request) => $request['items'][0]['payment_method_types'] === ['bank_transfer']
            && $request['items'][0]['amount_in_cents'] === $order->total_cents
            && $request->hasHeader('X-SECRET-KEY', 'sk_test_123'));

        // Las transferencias tienen más tiempo para acreditarse que un pago con tarjeta.
        $this->assertTrue($session->expiresAt->greaterThan(now()->addHours(20)));
    }

    public function test_reads_payment_method_and_transfer_in_progress(): void
    {
        $order = $this->orderPaidWith('bank_transfer');
        $payment = Payment::sole();
        $payment->update(['provider_checkout_id' => 'ch_1']);

        Http::fake(['*/checkouts/ch_1' => Http::response([
            'id' => 'ch_1',
            'status' => 'payment_in_progress',
            'total_in_cents' => $order->total_cents,
            'payment_method' => ['type' => 'bank_transfer'],
        ])]);

        $remote = $this->gateway()->fetchCheckout($payment);

        $this->assertFalse($remote->paid);
        $this->assertTrue($remote->processing);
        $this->assertSame('bank_transfer', $remote->paymentMethod);
    }
}

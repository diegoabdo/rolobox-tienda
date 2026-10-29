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
            && array_sum(array_column($request['items'], 'amount_in_cents')) === $order->total_cents
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

    public function test_each_product_and_the_shipping_go_as_their_own_item_summing_to_the_order_total(): void
    {
        $order = $this->orderPaidWith('card');
        Http::fake(['*/checkouts' => Http::response(['id' => 'ch_1', 'checkout_url' => 'https://pago.example/ch_1'], 201)]);

        $this->gateway()->createCheckout($order, Payment::sole(), 'https://ok', 'https://cancel');

        Http::assertSent(function (Request $request) use ($order): bool {
            $items = $request['items'];

            return count($items) === 2
                && $items[0]['name'] === 'Caja de seis roles: 3 canela clásica, 3 chocolate'
                && $items[1]['name'] === 'Envío'
                && array_sum(array_column($items, 'amount_in_cents')) === $order->total_cents;
        });
    }

    public function test_a_discount_is_spread_over_the_products_and_the_total_still_matches(): void
    {
        $this->seedCatalog();
        $this->addBoxToCart();
        $this->post('/checkout', $this->checkoutData(['promotion_code' => 'BIENVENIDA']))->assertRedirect();
        $order = Order::sole();
        $this->assertGreaterThan(0, $order->discount_cents);
        Http::fake(['*/checkouts' => Http::response(['id' => 'ch_1', 'checkout_url' => 'https://pago.example/ch_1'], 201)]);

        $this->gateway()->createCheckout($order, Payment::sole(), 'https://ok', 'https://cancel');

        Http::assertSent(fn (Request $request) => array_sum(array_column($request['items'], 'amount_in_cents')) === $order->total_cents);
    }
}

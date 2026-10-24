<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\OrderPayments;
use App\Services\Payments\RemoteCheckout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOrders;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
        $this->addBoxToCart();
    }

    public function test_totals_are_calculated_on_the_server(): void
    {
        $this->post('/checkout', $this->checkoutData(['promotion_code' => 'bienvenida']))
            ->assertRedirect(); // Inertia::location: redirige a la página de pago

        $order = Order::with('items', 'payments')->sole();

        // Con tarjeta: caja Q118.00 − 10% (BIENVENIDA aplica a cajas) + envío zona 3 Q15.00 = Q121.20
        $this->assertSame(11800, $order->subtotal_cents);
        $this->assertSame(1180, $order->discount_cents);
        $this->assertSame(1500, $order->shipping_cents);
        $this->assertSame(12120, $order->total_cents);
        $this->assertSame(PaymentMethod::Card, $order->payment_method);
        $this->assertSame('50255551234', $order->customer_phone);
        $this->assertSame('CF', $order->nit);
        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertSame(config('rolobox.legal_version'), $order->legal_version);
        $this->assertSame(6, collect($order->items->sole()->flavors)->sum('count'));
        $this->assertSame(12120, $order->payments->sole()->amount_cents);
    }

    public function test_paying_by_transfer_uses_the_transfer_price(): void
    {
        $this->post('/checkout', $this->checkoutData(['payment_method' => 'bank_transfer', 'promotion_code' => 'BIENVENIDA']))
            ->assertRedirect();

        $order = Order::with('items')->sole();

        // Con transferencia: caja Q110.00 − 10% + envío Q15.00 = Q114.00
        $this->assertSame(PaymentMethod::BankTransfer, $order->payment_method);
        $this->assertSame(11000, $order->subtotal_cents);
        $this->assertSame(1100, $order->discount_cents);
        $this->assertSame(11400, $order->total_cents);
        $this->assertSame(11000, $order->items->sole()->unit_price_cents);
    }

    public function test_payment_method_is_required(): void
    {
        $this->post('/checkout', $this->checkoutData(['payment_method' => 'efectivo']))
            ->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Order::count());
    }

    public function test_a_transfer_in_progress_does_not_open_another_payment(): void
    {
        $this->post('/checkout', $this->checkoutData(['payment_method' => 'bank_transfer']))->assertRedirect();
        $order = Order::sole();
        $payment = Payment::sole();

        $this->post("/pago-de-prueba/{$payment->id}/transferencia-iniciada")->assertRedirect();
        $this->assertSame(PaymentStatus::Processing, $payment->fresh()->status);

        $this->get("/pedido/{$order->reference}?token={$order->public_token}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('order.transferInProgress', true)
                ->where('canRetryPayment', false));

        // Pedir pagar de nuevo no crea otro cobro.
        $this->post("/pedido/{$order->reference}/pagar", ['token' => $order->public_token]);
        $this->assertSame(1, Payment::count());

        // Cuando se acredita, el pedido queda pagado.
        $this->post("/pago-de-prueba/{$payment->id}/pagar")->assertRedirect();
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertSame('bank_transfer', $payment->fresh()->method);
    }

    public function test_the_same_submission_does_not_create_two_orders(): void
    {
        $data = $this->checkoutData();

        $this->post('/checkout', $data)->assertRedirect();
        $this->post('/checkout', $data)->assertRedirect();

        $this->assertSame(1, Order::count());
        $this->assertSame(1, Payment::count());
    }

    public function test_local_delivery_needs_date_and_slot(): void
    {
        $this->post('/checkout', $this->checkoutData(['delivery_date' => '', 'delivery_slot' => '']))
            ->assertSessionHasErrors(['delivery_date', 'delivery_slot']);

        $this->post('/checkout', $this->checkoutData(['delivery_date' => now()->subDay()->toDateString()]))
            ->assertSessionHasErrors('delivery_date');

        $this->assertSame(0, Order::count());
    }

    public function test_invalid_phone_and_missing_terms_are_rejected(): void
    {
        $this->post('/checkout', $this->checkoutData(['phone' => '123', 'terms' => false]))
            ->assertSessionHasErrors(['phone', 'terms']);
    }

    public function test_inactive_zones_cannot_be_used(): void
    {
        $national = \App\Models\Zone::where('delivery_type', 'national')->firstOrFail();

        $this->post('/checkout', $this->checkoutData(['zone_id' => $national->id]))
            ->assertSessionHasErrors('zone_id');
    }

    public function test_first_order_promotion_is_rejected_for_returning_customers(): void
    {
        $this->post('/checkout', $this->checkoutData())->assertRedirect();
        $this->payLatest();

        $this->addBoxToCart();
        $this->post('/checkout', $this->checkoutData(['promotion_code' => 'BIENVENIDA']))
            ->assertSessionHasErrors('promotion_code');
    }

    public function test_paying_marks_the_order_and_redeems_the_promotion_once(): void
    {
        $this->post('/checkout', $this->checkoutData(['promotion_code' => 'BIENVENIDA']))->assertRedirect();

        $payment = $this->payLatest();
        $this->payLatest(); // segunda confirmación: no cambia nada

        $order = $payment->order->fresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
        $this->assertSame(1, $order->promotion->redemptions()->count());
    }

    public function test_a_different_paid_amount_is_not_accepted(): void
    {
        $this->post('/checkout', $this->checkoutData())->assertRedirect();
        $payment = Payment::sole();

        $paid = app(OrderPayments::class)->markPaid($payment, new RemoteCheckout('x', true, totalCents: 100));

        $this->assertFalse($paid);
        $this->assertSame(OrderStatus::PendingPayment, $payment->order->fresh()->status);

        // Sin monto informado tampoco se confirma.
        $this->assertFalse(app(OrderPayments::class)->markPaid($payment, new RemoteCheckout('x', true)));
        $this->assertSame(OrderStatus::PendingPayment, $payment->order->fresh()->status);
    }

    public function test_order_page_requires_the_secret_token(): void
    {
        $this->post('/checkout', $this->checkoutData())->assertRedirect();
        $order = Order::sole();

        $this->get("/pedido/{$order->reference}")->assertNotFound();
        $this->get("/pedido/{$order->reference}?token=incorrecto")->assertNotFound();
        $this->get("/pedido/{$order->reference}?token={$order->public_token}")->assertOk();
    }

    public function test_lookup_needs_reference_and_matching_phone(): void
    {
        $this->post('/checkout', $this->checkoutData())->assertRedirect();
        $order = Order::sole();

        $this->post('/seguir', ['reference' => $order->reference, 'phone' => '5555 9999'])
            ->assertSessionHasErrors('reference');

        $this->post('/seguir', ['reference' => strtolower($order->reference), 'phone' => '+502 5555-1234'])
            ->assertRedirect(route('orders.show', ['order' => $order->reference, 'token' => $order->public_token]));
    }

    private function payLatest(): Payment
    {
        $payment = Payment::latest('id')->firstOrFail();
        $this->post("/pago-de-prueba/{$payment->id}/pagar")->assertRedirect();

        return $payment;
    }
}

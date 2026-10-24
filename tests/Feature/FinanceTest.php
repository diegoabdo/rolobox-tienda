<?php

namespace Tests\Feature;

use App\Enums\ExpenseCategory;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\SalesChannel;
use App\Models\CashClosing;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\User;
use App\Services\InPersonSale;
use App\Services\SalesReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsOrders;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;

    private CarbonImmutable $today;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
        $this->today = CarbonImmutable::now(config('rolobox.timezone'))->startOfDay();

        // Costos de prueba: canela Q5 y chocolate Q6 por rol, caja Q3, bolsa del rol suelto Q1.
        $this->flavor('canela-clasica')->update(['cost_cents' => 500]);
        $this->flavor('chocolate')->update(['cost_cents' => 600]);
        $this->box()->update(['packaging_cost_cents' => 300]);
        $this->single()->update(['packaging_cost_cents' => 100]);
    }

    /** @param  array<string, mixed>  $overrides */
    private function sell(array $overrides = []): Order
    {
        return app(InPersonSale::class)->record(array_merge([
            'sold_on' => $this->today->toDateString(),
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $this->box()->id,
                    'quantity' => 1,
                    'flavors' => [
                        ['flavor_id' => $this->flavor('canela-clasica')->id, 'count' => 4],
                        ['flavor_id' => $this->flavor('chocolate')->id, 'count' => 2],
                    ],
                ],
                ['product_id' => $this->single()->id, 'quantity' => 2, 'flavor_id' => $this->flavor('chocolate')->id],
            ],
        ], $overrides));
    }

    private function expense(int $cents, string $method = 'cash'): Expense
    {
        return Expense::create([
            'spent_on' => $this->today->toDateString(),
            'category' => ExpenseCategory::Ingredients,
            'description' => 'Harina y mantequilla',
            'amount_cents' => $cents,
            'payment_method' => $method,
        ]);
    }

    public function test_in_person_cash_sale_uses_transfer_prices_and_keeps_costs(): void
    {
        $order = $this->sell(['customer_name' => 'Doña Marta', 'customer_phone' => '4444 1234']);

        // Efectivo: caja Q110 + 2 roles × Q20 = Q150, sin comisión.
        $this->assertSame(SalesChannel::InPerson, $order->channel);
        $this->assertSame(PaymentMethod::Cash, $order->payment_method);
        $this->assertSame(OrderStatus::Delivered, $order->status);
        $this->assertSame(15000, $order->total_cents);
        $this->assertSame(0, $order->payment_fee_cents);
        // Costo: caja (Q3 + 4×Q5 + 2×Q6 = Q35) + 2 roles (Q1 + Q6 = Q7 c/u) = Q49.
        $this->assertSame(4900, $order->cost_cents);
        $this->assertNotNull($order->paid_at);

        // El cliente queda guardado para compras futuras.
        $customer = Customer::where('phone', '50244441234')->sole();
        $this->assertSame('Doña Marta', $customer->name);
        $this->assertSame($customer->id, $order->customer_id);
    }

    public function test_pos_card_sale_uses_card_prices_and_pos_fee(): void
    {
        $order = $this->sell([
            'payment_method' => 'card',
            'items' => [['product_id' => $this->single()->id, 'quantity' => 1, 'flavor_id' => $this->flavor('canela-clasica')->id]],
        ]);

        // Rol con tarjeta Q23; comisión de POS 4.5% sin cargo fijo = Q1.04 (redondeado).
        $this->assertSame(2300, $order->total_cents);
        $this->assertSame(104, $order->payment_fee_cents);
        $this->assertNull($order->customer_id);
        $this->assertSame('Cliente en persona', $order->customer_name);
    }

    public function test_box_must_have_the_right_number_of_rolls(): void
    {
        $this->expectException(ValidationException::class);

        $this->sell(['items' => [[
            'product_id' => $this->box()->id,
            'quantity' => 1,
            'flavors' => [['flavor_id' => $this->flavor('chocolate')->id, 'count' => 5]],
        ]]]);
    }

    public function test_online_checkout_does_not_accept_cash(): void
    {
        $this->addBoxToCart();
        $this->post('/checkout', $this->checkoutData(['payment_method' => 'cash']))
            ->assertSessionHasErrors('payment_method');
    }

    public function test_expenses_reduce_net_profit(): void
    {
        $this->sell();
        $this->expense(2000);
        $this->expense(1000, 'card');

        $t = (new SalesReport($this->today, $this->today))->totals();

        $this->assertSame(15000, $t['cash']);
        $this->assertSame(15000, $t['inPerson']);
        $this->assertSame(0, $t['expectedDeposit']);
        $this->assertSame(15000 - 4900, $t['profit']);
        $this->assertSame(3000, $t['expenses']);
        $this->assertSame(2000, $t['cashExpenses']);
        $this->assertSame(15000 - 4900 - 3000, $t['netProfit']);
    }

    public function test_daily_closing_counts_cash_and_locks_the_day(): void
    {
        $this->sell();             // Q150 en efectivo
        $this->expense(2000);      // Q20 pagados de la caja

        // Fondo Q100 + Q150 − Q20 = Q230 esperado; se contaron Q225.
        $closing = CashClosing::create(CashClosing::snapshot(
            $this->today,
            $this->today,
            openingCashCents: 10000,
            countedCashCents: 22500,
        ));

        $this->assertSame(23000, $closing->expected_cash_cents);
        $this->assertSame(-500, $closing->cash_difference_cents);
        $this->assertSame(15000 - 4900 - 2000, $closing->net_profit_cents);
        $this->assertSame(22500, CashClosing::lastCountedCash());

        // El día cerrado ya no acepta ventas ni cambios de gastos.
        $this->assertTrue(Expense::sole()->isLocked());
        $this->expectException(ValidationException::class);
        $this->sell();
    }

    public function test_finance_pages_load(): void
    {
        $order = $this->sell(['customer_name' => 'Ana', 'customer_phone' => '5555 1234']);
        $expense = $this->expense(1500);
        $this->actingAs(User::factory()->create());

        foreach ([
            '/admin/gastos',
            '/admin/gastos/crear',
            "/admin/gastos/{$expense->id}/editar",
            '/admin/cierres/crear',
            '/admin/reporte-mensual',
            '/admin/pedidos',
            "/admin/pedidos/{$order->id}",
            '/admin/clientes/'.$order->customer_id,
            '/admin',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }
}

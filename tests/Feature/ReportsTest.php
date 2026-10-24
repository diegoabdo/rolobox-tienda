<?php

namespace Tests\Feature;

use App\Models\CashClosing;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\Zone;
use App\Services\SalesReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOrders;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();

        // Costos de prueba: canela Q5 y chocolate Q6 por rol, caja Q3, entregar en zona 3 cuesta Q8.
        $this->flavor('canela-clasica')->update(['cost_cents' => 500]);
        $this->flavor('chocolate')->update(['cost_cents' => 600]);
        $this->box()->update(['packaging_cost_cents' => 300]);
        Zone::where('name', 'Zona 3')->update(['delivery_cost_cents' => 800]);
    }

    /** Compra una caja (3 canela + 3 chocolate) a zona 3 y la paga. */
    private function buyBox(string $method): Order
    {
        $this->addBoxToCart();
        $this->post('/checkout', $this->checkoutData(['payment_method' => $method]))->assertRedirect();
        $payment = Payment::latest('id')->firstOrFail();
        $this->post("/pago-de-prueba/{$payment->id}/pagar")->assertRedirect();

        return $payment->order->fresh();
    }

    public function test_each_order_keeps_its_costs_and_profit(): void
    {
        $card = $this->buyBox('card');

        // Costo: caja Q3 + 3 × Q5 + 3 × Q6 = Q36.00
        $this->assertSame(3600, $card->cost_cents);
        $this->assertSame(800, $card->delivery_cost_cents);
        // Comisión con tarjeta sobre Q133.00: 4.5% (Q5.99) + Q2 = Q7.99
        $this->assertSame(13300, $card->total_cents);
        $this->assertSame(799, $card->payment_fee_cents);
        // Ganancia: 133.00 − 36.00 − 7.99 − 8.00 = Q81.01
        $this->assertSame(8101, $card->profitCents());

        $transfer = $this->buyBox('bank_transfer');

        // Con transferencia: Q125.00 sin comisión.
        $this->assertSame(0, $transfer->payment_fee_cents);
        $this->assertSame(12500 - 3600 - 800, $transfer->profitCents());

        // Si después cambia un costo, los pedidos ya hechos no cambian.
        $this->flavor('chocolate')->update(['cost_cents' => 900]);
        $this->assertSame(3600, $card->fresh()->cost_cents);
    }

    public function test_monthly_report_and_cash_closing(): void
    {
        $this->buyBox('card');
        $this->buyBox('bank_transfer');

        $today = CarbonImmutable::now(config('rolobox.timezone'));
        $report = SalesReport::month($today->year, $today->month);
        $t = $report->totals();

        $this->assertSame(2, $t['orders']);
        $this->assertSame(13300 + 12500, $t['gross']);
        $this->assertSame(13300, $t['card']);
        $this->assertSame(12500, $t['transfer']);
        $this->assertSame(799, $t['fees']);
        $this->assertSame(25800 - 799, $t['expectedDeposit']);
        $this->assertSame(8101 + 8100, $t['profit']);
        $this->assertSame(1, $t['customers']);

        $box = $report->byProduct()[0];
        $this->assertSame(2, $box['units']);
        $this->assertSame(11800 + 11000, $box['revenue']);
        $this->assertSame(7200, $box['cost']);
        $this->assertSame(['Canela clásica' => 6, 'Chocolate' => 6], $report->byFlavor());

        // Cierre: llegaron Q10 menos de lo esperado.
        $closing = CashClosing::create(CashClosing::snapshot($today, $today, 25001 - 1000));
        $this->assertSame(-1000, $closing->difference_cents);
        $this->assertSame(16201, $closing->profit_cents);
        // El siguiente día por cerrar sería mañana, pero nunca después de hoy.
        $this->assertTrue(CashClosing::nextPeriodStart()->isSameDay($today));
        $this->assertTrue(CashClosing::covers($today->toDateString()));
    }

    public function test_customers_reports_and_closings_pages_load(): void
    {
        $order = $this->buyBox('card');
        $closing = CashClosing::create(CashClosing::snapshot(CarbonImmutable::now(), CarbonImmutable::now()));
        $customer = Customer::sole();
        $this->actingAs(User::factory()->create());

        foreach ([
            '/admin/clientes',
            "/admin/clientes/{$customer->id}",
            "/admin/clientes/{$customer->id}/editar",
            '/admin/reporte-mensual',
            '/admin/cierres',
            '/admin/cierres/crear',
            '/admin/gastos',
            '/admin/gastos/crear',
            "/admin/cierres/{$closing->id}",
            "/admin/pedidos/{$order->id}",
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }
}

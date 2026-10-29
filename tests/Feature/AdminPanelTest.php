<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Flavor;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use App\Models\Zone;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOrders;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
    }

    private function placeOrder(): Order
    {
        $this->addBoxToCart();
        $this->post('/checkout', $this->checkoutData())->assertRedirect();

        return Order::latest('id')->firstOrFail();
    }

    public function test_guests_are_sent_to_the_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/pedidos')->assertRedirect('/admin/login');
    }

    public function test_every_admin_page_loads(): void
    {
        $order = $this->placeOrder();
        $this->actingAs(User::factory()->create());

        $urls = [
            '/admin',
            '/admin/pedidos',
            "/admin/pedidos/{$order->id}",
            '/admin/productos',
            '/admin/productos/crear',
            '/admin/productos/'.Product::first()->id.'/editar',
            '/admin/categorias',
            '/admin/categorias/crear',
            '/admin/categorias/'.\App\Models\Category::first()->id.'/editar',
            '/admin/sabores',
            '/admin/sabores/crear',
            '/admin/sabores/'.Flavor::first()->id.'/editar',
            '/admin/zonas',
            '/admin/zonas/crear',
            '/admin/zonas/'.Zone::first()->id.'/editar',
            '/admin/promociones',
            '/admin/promociones/crear',
            '/admin/promociones/'.Promotion::first()->id.'/editar',
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_orders_cannot_be_created_or_edited_from_the_panel(): void
    {
        $order = $this->placeOrder();
        $this->actingAs(User::factory()->create());

        $this->get('/admin/pedidos/create')->assertNotFound();
        $this->get("/admin/pedidos/{$order->id}/edit")->assertNotFound();
    }

    public function test_order_status_follows_the_allowed_steps(): void
    {
        $order = $this->placeOrder();

        // Sin pagar solo se puede cancelar.
        $this->assertSame([OrderStatus::Cancelled], $order->allowedTransitions());

        $this->post("/pago-de-prueba/{$order->payments()->first()->id}/pagar");
        $order->refresh();

        $order->moveTo(OrderStatus::Baking);
        $order->moveTo(OrderStatus::OutForDelivery);
        $this->assertFalse($order->canMoveTo(OrderStatus::Cancelled));

        $order->moveTo(OrderStatus::Delivered);
        $this->assertNotNull($order->fresh()->closed_at);

        $this->expectException(DomainException::class);
        $order->moveTo(OrderStatus::Baking);
    }
}

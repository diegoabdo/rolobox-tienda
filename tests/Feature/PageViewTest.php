<?php

namespace Tests\Feature;

use App\Filament\Widgets\VisitsBreakdown;
use App\Filament\Widgets\VisitsOverview;
use App\Models\Order;
use App\Models\PageView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrders;
use Tests\TestCase;

class PageViewTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
    }

    public function test_a_store_page_visit_is_recorded_without_personal_data(): void
    {
        $this->withHeaders(['Referer' => 'https://www.instagram.com/rolobox', 'User-Agent' => 'Mozilla/5.0 (iPhone)'])
            ->get('/armar-caja')
            ->assertOk();

        $view = PageView::sole();
        $this->assertSame('box', $view->page);
        $this->assertSame('instagram.com', $view->referrer);
        $this->assertTrue($view->is_mobile);
        $this->assertSame(64, strlen($view->visitor));
        $this->assertStringNotContainsString('127.0.0.1', $view->visitor);
    }

    public function test_the_same_visitor_keeps_one_fingerprint_during_the_day(): void
    {
        $this->get('/');
        $this->get('/carrito');

        $this->assertSame(1, PageView::distinct()->count('visitor'));
        $this->assertSame(2, PageView::count());
    }

    public function test_order_pages_are_stored_by_route_name_not_by_their_code(): void
    {
        $this->addBoxToCart();
        $this->post('/checkout', $this->checkoutData())->assertRedirect();
        PageView::query()->delete();

        $order = Order::latest('id')->firstOrFail();
        $this->get("/pedido/{$order->reference}?token={$order->public_token}")->assertOk();

        $this->assertSame('orders.show', PageView::sole()->page);
    }

    public function test_bots_admins_partial_reloads_and_prefetches_are_not_counted(): void
    {
        $this->withHeaders(['User-Agent' => 'Googlebot/2.1'])->get('/')->assertOk();
        $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Partial-Component' => 'Home', 'X-Inertia-Partial-Data' => 'promotion'])->get('/');
        $this->withHeaders(['Purpose' => 'prefetch'])->get('/');
        $this->actingAs(User::factory()->create())->get('/')->assertOk();

        $this->assertSame(0, PageView::count());
    }

    public function test_redirects_and_posts_are_not_visits(): void
    {
        $this->get('/checkout')->assertRedirect('/carrito');
        $this->addBoxToCart();

        $this->assertSame(0, PageView::count());
    }

    public function test_the_dashboard_shows_the_visit_widgets(): void
    {
        $this->get('/');
        $this->actingAs(User::factory()->create());

        $this->get('/admin')->assertOk();
        Livewire::test(VisitsOverview::class)->assertSee('Visitantes hoy');
        Livewire::test(VisitsBreakdown::class)->assertSee('Recorrido hasta pagar')->assertSee('Inicio');
    }
}

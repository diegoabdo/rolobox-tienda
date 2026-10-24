<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOrders;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
    }

    public function test_public_pages_load(): void
    {
        foreach (['/', '/armar-caja', '/carrito', '/seguir', '/terminos', '/privacidad'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_checkout_with_empty_cart_goes_back_to_cart(): void
    {
        $this->get('/checkout')->assertRedirect('/carrito');
    }

    public function test_box_requires_exactly_six_rolls(): void
    {
        $this->post('/carrito', [
            'product_id' => $this->box()->id,
            'flavors' => [$this->flavor('chocolate')->id => 5],
        ])->assertSessionHasErrors('flavors');

        $this->addBoxToCart();
        $this->get('/checkout')->assertOk();
    }

    public function test_single_roll_needs_one_flavor(): void
    {
        $this->post('/carrito', [
            'product_id' => $this->single()->id,
            'flavors' => [$this->flavor('chocolate')->id => 1, $this->flavor('canela-clasica')->id => 1],
        ])->assertSessionHasErrors('flavors');

        $this->post('/carrito', [
            'product_id' => $this->single()->id,
            'flavors' => [$this->flavor('chocolate')->id => 1],
            'quantity' => 2,
        ])->assertSessionHasNoErrors();
    }

    public function test_unpublished_products_cannot_be_added(): void
    {
        $this->box()->update(['published' => false]);

        $this->post('/carrito', [
            'product_id' => $this->box()->id,
            'flavors' => [$this->flavor('chocolate')->id => 6],
        ])->assertSessionHasErrors('product');
    }
}

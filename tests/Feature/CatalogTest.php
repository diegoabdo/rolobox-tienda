<?php

namespace Tests\Feature;

use App\Models\Flavor;
use App\Models\Product;
use App\Services\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsOrders;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
    }

    private function product(string $slug): Product
    {
        return Product::where('slug', $slug)->firstOrFail();
    }

    /** La caja mixta viene desactivada; las pruebas que la usan la publican como lo haría el admin. */
    private function enableMixedBox(): void
    {
        $this->product('caja-mixta-de-seis')->update(['published' => true]);
    }

    private function addSingle(string $productSlug, string $flavorSlug): void
    {
        $this->post('/carrito', [
            'product_id' => $this->product($productSlug)->id,
            'flavors' => [$this->flavor($flavorSlug)->id => 1],
        ]);
    }

    public function test_the_home_page_lists_every_category_with_its_flavors_and_products(): void
    {
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('categories', 4)
            ->where('categories.0.name', 'Rolls de canela')
            ->has('flavors', 14)
            ->has('products', 8));
    }

    public function test_a_hidden_category_disappears_from_the_store(): void
    {
        $this->flavor('croissant-almendra')->category->update(['active' => false]);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->has('categories', 3)
            ->has('flavors', 10)
            ->has('products', 6));
    }

    public function test_a_single_product_adds_the_flavor_extra_to_its_price(): void
    {
        $this->addSingle('croissant', 'croissant-almendra');

        $line = app(Cart::class)->resolve()['lines'][0];
        $this->assertSame(2100, $line['unitPriceCents']);        // Q18 + Q3 de almendra
        $this->assertSame(1950, $line['transferUnitPriceCents']); // Q16.50 + Q3
        $this->assertSame('Croissant de almendra', $line['label']);
    }

    public function test_a_flavor_from_another_category_cannot_be_ordered_with_a_product(): void
    {
        $this->addSingle('croissant', 'canela-clasica');

        $this->assertTrue(app(Cart::class)->isEmpty());
    }

    public function test_a_category_box_only_takes_flavors_marked_for_it(): void
    {
        $box = $this->product('caja-de-cuatro-croissants');

        $this->post('/carrito', ['product_id' => $box->id, 'flavors' => [$this->flavor('croissant-mantequilla')->id => 2, $this->flavor('croissant-almendra')->id => 2]])
            ->assertSessionHasNoErrors();

        // Un rol no entra en la caja de croissants.
        $this->post('/carrito', ['product_id' => $box->id, 'flavors' => [$this->flavor('canela-clasica')->id => 4]])
            ->assertSessionHasErrors('flavors');

        // Si el admin lo apaga, ese sabor sale de la caja.
        $this->flavor('croissant-chocolate')->update(['box_eligible' => false]);
        $this->post('/carrito', ['product_id' => $box->id, 'flavors' => [$this->flavor('croissant-chocolate')->id => 4]])
            ->assertSessionHasErrors('flavors');
    }

    public function test_a_mixed_box_takes_flavors_from_several_categories_but_only_those_marked_for_it(): void
    {
        $this->enableMixedBox();

        $mixed = $this->product('caja-mixta-de-seis');

        $this->post('/carrito', ['product_id' => $mixed->id, 'flavors' => [
            $this->flavor('canela-clasica')->id => 3,
            $this->flavor('croissant-almendra')->id => 3,
        ]])->assertSessionHasNoErrors();

        // La focaccia no está marcada para cajas mixtas.
        $this->post('/carrito', ['product_id' => $mixed->id, 'flavors' => [
            $this->flavor('canela-clasica')->id => 5,
            $this->flavor('focaccia-romero-y-sal')->id => 1,
        ]])->assertSessionHasErrors('flavors');
    }

    public function test_a_box_needs_exactly_its_size_in_pieces(): void
    {
        $this->enableMixedBox();

        $this->post('/carrito', ['product_id' => $this->product('caja-mixta-de-seis')->id, 'flavors' => [$this->flavor('canela-clasica')->id => 5]])
            ->assertSessionHasErrors('flavors');
    }

    public function test_a_cart_line_is_dropped_when_its_flavor_stops_being_accepted(): void
    {
        $this->enableMixedBox();

        $this->post('/carrito', ['product_id' => $this->product('caja-mixta-de-seis')->id, 'flavors' => [
            $this->flavor('canela-clasica')->id => 3,
            $this->flavor('croissant-almendra')->id => 3,
        ]])->assertSessionHasNoErrors();

        $this->flavor('croissant-almendra')->update(['mix_eligible' => false]);

        $cart = app(Cart::class)->resolve();
        $this->assertSame([], $cart['lines']);
        $this->assertSame(1, $cart['removed']);
    }

    public function test_the_box_page_opens_the_box_that_takes_the_chosen_flavor(): void
    {
        $this->get('/armar-caja?sabor=croissant-almendra')->assertInertia(fn (Assert $page) => $page
            ->component('ArmarCaja')
            ->where('selectedBox', 'caja-de-cuatro-croissants')
            ->has('boxes', 2));

        // Con la caja mixta desactivada, pedirla por su identificador abre la primera caja.
        $this->get('/armar-caja?caja=caja-mixta-de-seis')->assertInertia(fn (Assert $page) => $page
            ->where('selectedBox', 'caja-de-seis'));

        $this->enableMixedBox();
        $this->get('/armar-caja?caja=caja-mixta-de-seis')->assertInertia(fn (Assert $page) => $page
            ->where('selectedBox', 'caja-mixta-de-seis')
            ->has('boxes', 3));

        // Una focaccia no entra en ninguna caja: abre la primera.
        $this->get('/armar-caja?sabor=focaccia-romero-y-sal')->assertInertia(fn (Assert $page) => $page
            ->where('selectedBox', 'caja-de-seis'));
    }

    public function test_every_flavor_belongs_to_the_category_of_its_products(): void
    {
        $this->assertSame(0, Flavor::whereNull('category_id')->count());
        $this->assertTrue($this->product('rol-suelto')->accepts($this->flavor('chocolate')));
        $this->assertFalse($this->product('rol-suelto')->accepts($this->flavor('croissant-almendra')));
    }

    public function test_the_mixed_box_is_off_by_default_and_cannot_be_ordered(): void
    {
        $this->assertFalse($this->product('caja-mixta-de-seis')->published);

        $this->post('/carrito', ['product_id' => $this->product('caja-mixta-de-seis')->id, 'flavors' => [
            $this->flavor('canela-clasica')->id => 3,
            $this->flavor('croissant-almendra')->id => 3,
        ]])->assertSessionHasErrors('product');

        $this->assertTrue(app(Cart::class)->isEmpty());
    }
}

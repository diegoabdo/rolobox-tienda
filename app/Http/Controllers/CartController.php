<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToCartRequest;
use App\Models\Product;
use App\Services\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(private readonly Cart $cart) {}

    public function show(): Response
    {
        $cart = $this->cart->resolve();

        return Inertia::render('Carrito', [
            'cart' => $cart,
        ]);
    }

    public function store(AddToCartRequest $request): RedirectResponse
    {
        $product = Product::findOrFail($request->integer('product_id'));

        $this->cart->add(
            $product,
            $request->input('flavors', []),
            $request->integer('quantity', 1),
            $request->input('gift_message'),
        );

        $message = $product->isBox() ? 'Tu caja está en el carrito.' : 'Agregado al carrito.';

        if ($request->boolean('go_to_cart')) {
            return redirect()->route('cart.show')->with('status', $message);
        }

        return back()->with('status', $message);
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:'.config('rolobox.cart.max_quantity_per_line')],
        ]);

        $this->cart->updateQuantity($key, (int) $data['quantity']);

        return back();
    }

    public function destroy(string $key): RedirectResponse
    {
        $this->cart->remove($key);

        return back()->with('status', 'Quitamos el producto del carrito.');
    }
}

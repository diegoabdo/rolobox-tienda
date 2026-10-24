<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryType;
use App\Enums\PaymentMethod;
use App\Http\Requests\CheckoutRequest;
use App\Models\Zone;
use App\Services\Cart;
use App\Services\Checkout;
use App\Services\Pricing;
use App\Support\DeliverySchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CheckoutController extends Controller
{
    public function show(Request $request, Cart $cart, Pricing $pricing): Response|RedirectResponse
    {
        $resolved = $cart->resolve();
        if ($resolved['lines'] === []) {
            return redirect()->route('cart.show');
        }

        $zones = Zone::active()->get();
        $zone = $request->filled('zona') ? $zones->firstWhere('id', $request->integer('zona')) : null;
        $code = $request->string('cupon')->substr(0, 40)->toString() ?: null;

        // Se calculan los dos totales: el cliente ve cuánto paga con tarjeta y cuánto con transferencia.
        $quotes = [];
        foreach (PaymentMethod::online() as $method) {
            $quote = $pricing->quote($resolved, $zone, $code, null, $method);
            $quotes[$method->value] = [
                'zoneId' => $zone?->id,
                'subtotalCents' => $quote['subtotalCents'],
                'discountCents' => $quote['discountCents'],
                'shippingCents' => $quote['shippingCents'],
                'totalCents' => $quote['totalCents'],
                'freeShipping' => $quote['freeShipping'],
                'promotionCode' => $quote['promotion']?->code,
                'promotionDescription' => $quote['promotion']?->description,
                'promotionError' => $quote['promotionError'],
            ];
        }

        return Inertia::render('Checkout', [
            'cart' => $resolved,
            'zones' => [
                'local' => $zones->where('delivery_type', DeliveryType::Local)->map->toStorefront()->values(),
                'national' => $resolved['nationalReady']
                    ? $zones->where('delivery_type', DeliveryType::National)->map->toStorefront()->values()
                    : [],
            ],
            'nationalBlocked' => ! $resolved['nationalReady']
                && $zones->contains('delivery_type', DeliveryType::National),
            'dates' => DeliverySchedule::availableDates(),
            'slots' => DeliverySchedule::slots(),
            'quotes' => $quotes,
            'idempotencyKey' => (string) Str::uuid(),
            'legalVersion' => config('rolobox.legal_version'),
        ]);
    }

    public function store(CheckoutRequest $request, Checkout $checkout): SymfonyResponse
    {
        $url = $checkout->place($request->validated());

        return Inertia::location($url);
    }
}

<?php

namespace App\Http\Middleware;

use App\Services\Cart;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'cartCount' => fn () => app(Cart::class)->count(),
            'flash' => fn () => [
                'status' => $request->session()->get('status'),
            ],
            'contact' => [
                'whatsapp' => config('rolobox.contact.whatsapp'),
                'email' => config('rolobox.contact.email'),
            ],
        ];
    }
}

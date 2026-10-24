<?php

namespace App\Providers;

use App\Services\Cart;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\RecurrenteGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(Cart::class, fn ($app) => new Cart($app['session.store']));

        $this->app->singleton(PaymentGateway::class, function () {
            return match (config('rolobox.payments.driver')) {
                'recurrente' => new RecurrenteGateway(
                    baseUrl: (string) config('rolobox.payments.recurrente.base_url'),
                    secretKey: config('rolobox.payments.recurrente.secret_key'),
                ),
                'fake' => $this->app->isProduction()
                    ? throw new InvalidArgumentException('El procesador simulado no se puede usar en producción.')
                    : new FakeGateway,
                default => throw new InvalidArgumentException('PAYMENTS_DRIVER desconocido.'),
            };
        });
    }

    public function boot(): void
    {
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('lookup', fn (Request $request) => [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perDay(50)->by($request->ip()),
        ]);
    }
}

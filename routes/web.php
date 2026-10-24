<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FakePaymentController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\Webhooks\RecurrenteWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StoreController::class, 'home'])->name('home');
Route::get('/armar-caja', [StoreController::class, 'box'])->name('box');

Route::get('/carrito', [CartController::class, 'show'])->name('cart.show');
Route::post('/carrito', [CartController::class, 'store'])->middleware('throttle:60,1')->name('cart.store');
Route::patch('/carrito/{key}', [CartController::class, 'update'])->where('key', '[a-f0-9]{16}')->name('cart.update');
Route::delete('/carrito/{key}', [CartController::class, 'destroy'])->where('key', '[a-f0-9]{16}')->name('cart.destroy');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');

Route::get('/pago/retorno/{order:reference}', [PaymentController::class, 'return'])->name('payments.return');
Route::get('/pago/cancelado/{order:reference}', [PaymentController::class, 'cancelled'])->name('payments.cancelled');

Route::get('/pedido/{order:reference}', [OrderController::class, 'show'])->name('orders.show');
Route::post('/pedido/{order:reference}/pagar', [OrderController::class, 'pay'])->middleware('throttle:checkout')->name('orders.pay');
Route::get('/seguir', [OrderController::class, 'lookupForm'])->name('orders.lookup');
Route::post('/seguir', [OrderController::class, 'lookup'])->middleware('throttle:lookup')->name('orders.lookup.submit');

Route::get('/terminos', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/privacidad', [LegalController::class, 'privacy'])->name('legal.privacy');

Route::post('/webhooks/recurrente', RecurrenteWebhookController::class)->middleware('throttle:120,1')->name('webhooks.recurrente');

// Procesador simulado: solo en desarrollo y con PAYMENTS_DRIVER=fake.
if (config('rolobox.payments.driver') === 'fake' && ! app()->isProduction()) {
    Route::get('/pago-de-prueba/{payment}', [FakePaymentController::class, 'show'])->name('fake-payment.show');
    Route::post('/pago-de-prueba/{payment}/pagar', [FakePaymentController::class, 'confirm'])->name('fake-payment.confirm');
    Route::post('/pago-de-prueba/{payment}/transferencia-iniciada', [FakePaymentController::class, 'pending'])->name('fake-payment.pending');
    Route::post('/pago-de-prueba/{payment}/cancelar', [FakePaymentController::class, 'cancel'])->name('fake-payment.cancel');
}

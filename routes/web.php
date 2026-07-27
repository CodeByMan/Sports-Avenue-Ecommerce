<?php

declare(strict_types=1);

use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::view('/profile', 'profile.edit')->name('profile.edit');

    Route::middleware('verified')->group(function (): void {
        Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])
            ->name('checkout.success');
        Route::get('/payment/finish', [CheckoutController::class, 'finish'])
            ->name('checkout.payment.finish');
        Route::get('/payment/unfinish', [CheckoutController::class, 'unfinish'])
            ->name('checkout.payment.unfinish');
        Route::get('/payment/error', [CheckoutController::class, 'error'])
            ->name('checkout.payment.error');
    });
});

foreach (glob(__DIR__.'/extends/*.php') as $route) {
    require $route;
}

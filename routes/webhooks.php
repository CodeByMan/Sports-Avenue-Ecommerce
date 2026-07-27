<?php

declare(strict_types=1);

use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::post('/payment/webhook', [CheckoutController::class, 'webhook'])
    ->name('payment.webhook');

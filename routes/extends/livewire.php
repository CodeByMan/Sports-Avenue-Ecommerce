<?php

use App\Livewire\Cart;
use App\Livewire\Home;
use App\Livewire\Jersey;
use App\Livewire\JerseyDetail;
use App\Livewire\League;
use App\Livewire\OrderHistory;
use Illuminate\Support\Facades\Route;

Route::get('/', Home::class)
    ->name('home');
Route::get('/leagues', League::class)
    ->name('league');
Route::get('/jerseys', Jersey::class)
    ->name('jersey');
Route::get('/jersey/{jersey}', JerseyDetail::class)
    ->name('jersey.detail');

Route::middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/cart', Cart::class)
            ->name('cart');
        Route::get('/order-history', OrderHistory::class)
            ->name('order-history');
    });

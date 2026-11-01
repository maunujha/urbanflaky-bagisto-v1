<?php

use Gabha\Notifications\Http\Controllers\Shop\CartRecoveryController;
use Gabha\Notifications\Http\Controllers\Shop\EmailPreferenceController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'signed', 'throttle:30,1'])->prefix('email')->group(function () {
    Route::get('bag/{cart}', CartRecoveryController::class)
        ->whereNumber('cart')
        ->name('shop.notifications.cart.recover');

    Route::get('unsubscribe', [EmailPreferenceController::class, 'show'])
        ->name('shop.notifications.unsubscribe');

    /* Mail clients POST here for one-click unsubscribe, with no CSRF token; the signature protects it. */
    Route::post('unsubscribe', [EmailPreferenceController::class, 'unsubscribe'])
        ->withoutMiddleware([ValidateCsrfToken::class])
        ->name('shop.notifications.unsubscribe.store');
});

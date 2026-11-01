<?php

use Gabha\Notifications\Http\Controllers\Shop\CartRecoveryController;
use Gabha\Notifications\Http\Controllers\Shop\EmailPreferenceController;
use Gabha\Notifications\Http\Controllers\Shop\FeedbackController;
use Gabha\Notifications\Http\Controllers\Shop\StockAlertController;
use Gabha\Notifications\Http\Controllers\Shop\SubscriptionConfirmController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

/* Links from emails: every one is signed, so it cannot be guessed or altered. */
Route::middleware(['web', 'signed', 'throttle:30,1'])->prefix('email')->group(function () {
    Route::get('bag/{cartId}', CartRecoveryController::class)
        ->whereNumber('cartId')
        ->name('shop.notifications.cart.recover');

    Route::get('unsubscribe', [EmailPreferenceController::class, 'show'])
        ->name('shop.notifications.unsubscribe');

    /* Mail clients POST here for one-click unsubscribe, with no CSRF token; the signature protects it. */
    Route::post('unsubscribe', [EmailPreferenceController::class, 'unsubscribe'])
        ->withoutMiddleware([ValidateCsrfToken::class])
        ->name('shop.notifications.unsubscribe.store');

    Route::get('confirm-subscription/{subscriber}', SubscriptionConfirmController::class)
        ->whereNumber('subscriber')
        ->name('shop.notifications.subscription.confirm');

    Route::get('feedback/{orderId}/{rating}', [FeedbackController::class, 'show'])
        ->whereNumber(['orderId', 'rating'])
        ->name('shop.notifications.feedback.show');

    Route::post('feedback/{orderId}/{rating}', [FeedbackController::class, 'store'])
        ->whereNumber(['orderId', 'rating'])
        ->name('shop.notifications.feedback.store');
});

Route::middleware(['web', 'throttle:10,1'])->group(function () {
    Route::post('stock-alerts', [StockAlertController::class, 'store'])
        ->name('shop.notifications.stock-alerts.store');
});

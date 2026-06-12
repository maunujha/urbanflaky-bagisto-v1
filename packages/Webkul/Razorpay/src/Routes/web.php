<?php

use Illuminate\Support\Facades\Route;
use Webkul\Razorpay\Http\Controllers\RazorpayController;

Route::controller(RazorpayController::class)
    ->middleware('web')
    ->prefix('razorpay/payment')
    ->group(function () {
        Route::get('redirect', 'redirect')->name('razorpay.payment.redirect');

        Route::get('success', 'paymentSuccess')->name('razorpay.payment.success');

        Route::get('fail', 'paymentFail')->name('razorpay.payment.cancel');

        /*
         * Server-to-server webhook (Razorpay dashboard → here). Recovers orders
         * for payments captured when the customer's browser never returned.
         * Signature-verified inside the controller; CSRF-exempt.
         */
        Route::post('webhook', 'webhook')
            ->name('razorpay.payment.webhook')
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    });

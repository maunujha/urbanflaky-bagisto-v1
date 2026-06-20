<?php

use App\Http\Controllers\DeliveryCheckController;
use App\Http\Controllers\ShiprocketWebhookController;
use App\Http\Controllers\Shop\CookieConsentController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::post('check-delivery', [DeliveryCheckController::class, 'check'])
    ->middleware('throttle:20,1')
    ->name('check.delivery');

/* Cookie consent — mirrors a visitor's choice to the DB for logged-in customers. */
Route::post('cookie-consent', [CookieConsentController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('cookie.consent.store');

Route::post('webhooks/tracking', [ShiprocketWebhookController::class, 'handle'])
    ->name('webhooks.shiprocket')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

/* Public XML sitemap (declared here so it wins over the shop catalog fallback). */
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

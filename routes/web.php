<?php

use App\Http\Controllers\DeliveryCheckController;
use App\Http\Controllers\ShiprocketWebhookController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::post('check-delivery', [DeliveryCheckController::class, 'check'])
    ->middleware('throttle:20,1')
    ->name('check.delivery');

Route::post('webhooks/tracking', [ShiprocketWebhookController::class, 'handle'])
    ->name('webhooks.shiprocket')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

/* Public XML sitemap (declared here so it wins over the shop catalog fallback). */
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

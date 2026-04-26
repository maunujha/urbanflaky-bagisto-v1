<?php

use App\Http\Controllers\ShiprocketWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/tracking', [ShiprocketWebhookController::class, 'handle'])
    ->name('webhooks.shiprocket')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

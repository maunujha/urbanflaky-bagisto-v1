<?php

use Illuminate\Support\Facades\Route;
use Webkul\FAQ\Http\Controllers\Shop\FaqController;

Route::middleware(['web'])->group(function () {
    /**
     * Public FAQ page.
     */
    Route::get('faqs', [FaqController::class, 'index'])
        ->name('shop.faqs.index')
        ->middleware('cache.response');

    /**
     * FAQ search (AJAX autocomplete).
     */
    Route::get('api/faqs/search', [FaqController::class, 'search'])
        ->name('shop.api.faqs.search')
        ->middleware('throttle:api');
});

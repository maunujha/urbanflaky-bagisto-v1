<?php

use App\Http\Controllers\Shop\LookbookController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['web', 'shop'],
    'prefix'     => 'api',
], function () {
    Route::controller(LookbookController::class)->prefix('lookbook')->group(function () {
        Route::get('', 'index')->name('shop.api.lookbook.index');
    });
});

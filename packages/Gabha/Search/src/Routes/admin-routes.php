<?php

use Gabha\Search\Http\Controllers\Admin\SearchInsightsController;
use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;

Route::group(['middleware' => ['web', 'admin', NoCacheMiddleware::class], 'prefix' => config('app.admin_url')], function () {
    /**
     * Search Insights (natural-language search analytics).
     */
    Route::controller(SearchInsightsController::class)->prefix('search/insights')->group(function () {
        Route::get('', 'index')->name('admin.search.insights.index');

        Route::post('mass-delete', 'massDelete')->name('admin.search.insights.mass_delete');
    });
});

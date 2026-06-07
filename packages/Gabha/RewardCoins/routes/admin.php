<?php

use Gabha\RewardCoins\Http\Controllers\Admin\CoinAdminController;
use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;

/*
|--------------------------------------------------------------------------
| Reward Coins — Admin routes
|--------------------------------------------------------------------------
|
| Back-office dashboard, settings, and per-customer detail/grant.
|
*/

Route::group([
    'middleware' => ['web', 'admin', NoCacheMiddleware::class],
    'prefix'     => config('app.admin_url'),
], function () {
    Route::controller(CoinAdminController::class)->prefix('reward-coins')->group(function () {
        Route::get('', 'index')->name('admin.reward_coins.index');

        Route::get('settings', 'settings')->name('admin.reward_coins.settings');

        Route::post('settings', 'updateSettings')->name('admin.reward_coins.settings.update');

        Route::get('customers', 'customers')->name('admin.reward_coins.customers');

        Route::get('customer/{id}', 'customerDetail')->name('admin.reward_coins.customer');

        Route::post('customer/{id}/grant', 'grantCoins')->name('admin.reward_coins.customer.grant');

        Route::post('customer/{id}/approve', 'approveCoins')->name('admin.reward_coins.customer.approve');
    });
});

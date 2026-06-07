<?php

use Gabha\RewardCoins\Http\Controllers\Shop\CoinController;
use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;

/*
|--------------------------------------------------------------------------
| Reward Coins — Shop (storefront) routes
|--------------------------------------------------------------------------
|
| All customer-authenticated: the My Coins account page plus the checkout
| apply/remove staging endpoints.
|
*/

Route::middleware(['web', 'customer', NoCacheMiddleware::class])->group(function () {
    /*
     * My Coins account page.
     */
    Route::get('customer/account/coins', [CoinController::class, 'index'])
        ->name('shop.customers.account.coins.index');

    /*
     * Checkout redemption staging (AJAX).
     */
    Route::post('checkout/coins/apply', [CoinController::class, 'apply'])
        ->name('shop.checkout.coins.apply');

    Route::post('checkout/coins/remove', [CoinController::class, 'remove'])
        ->name('shop.checkout.coins.remove');
});

<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Providers;

use Gabha\RewardCoins\Checkout\CoinDiscount;
use Gabha\RewardCoins\Console\ExpireCoinsCommand;
use Gabha\RewardCoins\Listeners\AwardCoinsOnOrder;
use Gabha\RewardCoins\Listeners\ConfirmCoinsOnDelivery;
use Gabha\RewardCoins\Listeners\RedeemCoinsOnOrder;
use Gabha\RewardCoins\Listeners\ReverseCoinsOnCancellation;
use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Repositories\CoinTransactionRepository;
use Gabha\RewardCoins\Repositories\Contracts\CoinTransactionRepositoryInterface;
use Gabha\RewardCoins\Repositories\Contracts\CoinWalletRepositoryInterface;
use Gabha\RewardCoins\Repositories\CoinWalletRepository;
use Gabha\RewardCoins\Rules\CategoryMultiplierRule;
use Gabha\RewardCoins\Rules\ExcludeDiscountedItemsRule;
use Gabha\RewardCoins\Rules\MinimumOrderRule;
use Gabha\RewardCoins\Services\CoinEarningCalculator;
use Gabha\RewardCoins\ViewComposers\CoinBalanceComposer;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Root service provider for the Gabha RewardCoins package.
 *
 * Wires the package into Bagisto purely through Laravel's extension points
 * (config merge, container bindings, event listeners, view composers, route /
 * view / migration loaders) — zero Bagisto core files are touched.
 */
class RewardCoinsServiceProvider extends ServiceProvider
{
    /**
     * Absolute path to the package root (one level above `src/`).
     */
    private string $packageRoot;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->packageRoot = dirname(__DIR__, 2);

        $this->mergeConfigFrom($this->packageRoot.'/src/Config/reward_coins.php', 'reward_coins');
        $this->mergeConfigFrom($this->packageRoot.'/src/Config/menu.php', 'menu.admin');
        $this->mergeConfigFrom($this->packageRoot.'/src/Config/customer_menu.php', 'menu.customer');
        $this->mergeConfigFrom($this->packageRoot.'/src/Config/acl.php', 'acl');

        $this->registerBindings();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $viewNamespace = config('reward_coins.view_namespace', 'reward-coins');

        $this->loadMigrationsFrom($this->packageRoot.'/src/Database/Migrations');
        $this->loadRoutesFrom($this->packageRoot.'/routes/shop.php');
        $this->loadRoutesFrom($this->packageRoot.'/routes/admin.php');
        $this->loadViewsFrom($this->packageRoot.'/resources/views', $viewNamespace);
        $this->loadTranslationsFrom($this->packageRoot.'/resources/lang', $viewNamespace);

        $this->registerPublishables();
        $this->shareCoinBalance();
        $this->registerListeners();
        $this->registerCommands();
    }

    /**
     * Bind contracts to implementations and assemble the earning pipeline.
     *
     * @return void
     */
    private function registerBindings(): void
    {
        $this->app->bind(CoinTransactionRepositoryInterface::class, CoinTransactionRepository::class);
        $this->app->bind(CoinWalletRepositoryInterface::class, CoinWalletRepository::class);

        /*
         * Inject the cached active settings wherever a CoinSetting is type-hinted
         * (rules, calculator, services) so they share one source of truth.
         */
        $this->app->bind(CoinSetting::class, fn (): CoinSetting => CoinSetting::active());

        /*
         * The ordered earning-rule pipeline. Add or remove rules here — the
         * calculator stays untouched (open/closed).
         */
        $this->app->when(CoinEarningCalculator::class)
            ->needs('$rules')
            ->give([
                MinimumOrderRule::class,
                ExcludeDiscountedItemsRule::class,
                CategoryMultiplierRule::class,
            ]);
    }

    /**
     * Expose package config to `php artisan vendor:publish`.
     *
     * @return void
     */
    private function registerPublishables(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            $this->packageRoot.'/src/Config/reward_coins.php' => config_path('reward_coins.php'),
        ], 'reward-coins-config');
    }

    /**
     * Expose the customer's coin balance as `$coinBalance` to the storefront.
     *
     * @return void
     */
    private function shareCoinBalance(): void
    {
        /*
         * The header partials render as anonymous Blade components
         * (<x-shop::layouts.header...>), and Laravel does NOT run view composers
         * for anonymous components — so a View::composer can never reach them.
         * Instead we globally share the balance once per storefront page, hooked
         * on the layout's head render event (fired after auth, before the header
         * renders). Shared data is merged into every view, components included.
         */
        Event::listen('bagisto.shop.layout.head.before', function (): void {
            View::share('coinBalance', $this->app->make(CoinBalanceComposer::class)->balance());
        });
    }

    /**
     * Map Bagisto order events to the coin listeners.
     *
     * @return void
     */
    private function registerListeners(): void
    {
        /*
         * Fold the staged coin redemption into the cart totals. Bagisto has no
         * pluggable total-collector interface, so we hook the cart-collect event
         * (the supported, core-free extension point). The adjusted grand total
         * flows through to the order and the payment amount.
         */
        Event::listen('checkout.cart.collect.totals.after', CoinDiscount::class);

        /*
         * Debit the wallet + record coins_redeemed when the order is placed.
         * Registered before AwardCoinsOnOrder so the redeemed count is persisted
         * before the (queued) earning listener reads it back.
         */
        Event::listen('checkout.order.save.after', RedeemCoinsOnOrder::class);
        Event::listen('checkout.order.save.after', AwardCoinsOnOrder::class);

        Event::listen('sales.order.update-status.after', ConfirmCoinsOnDelivery::class);
        Event::listen('sales.order.update-status.after', ReverseCoinsOnCancellation::class);
    }

    /**
     * Register the artisan command and its daily schedule.
     *
     * @return void
     */
    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ExpireCoinsCommand::class]);
        }

        $this->app->booted(function (): void {
            $this->app->make(Schedule::class)
                ->command('reward-coins:expire')
                ->dailyAt('00:00');
        });
    }
}

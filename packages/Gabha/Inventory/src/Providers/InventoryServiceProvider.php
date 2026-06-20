<?php

declare(strict_types=1);

namespace Gabha\Inventory\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Root service provider for the Gabha Inventory package.
 *
 * Wires the inventory module into Bagisto purely through Laravel's extension
 * points (config merge plus route / view / migration / translation loaders) —
 * no Bagisto core files are modified by this provider.
 */
class InventoryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'inventory');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'inventory');
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/menu.php',
            'menu.admin'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/acl.php',
            'acl'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/inventory.php',
            'inventory'
        );
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class LookbookServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(base_path('config/lookbook-menu.php'), 'menu.admin');

        $this->mergeConfigFrom(base_path('config/lookbook-acl.php'), 'acl');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('routes/lookbook-admin.php'));
        $this->loadRoutesFrom(base_path('routes/lookbook-shop.php'));

        $this->loadViewsFrom(resource_path('views/lookbook'), 'lookbook');

        $this->loadTranslationsFrom(base_path('lang/lookbook'), 'lookbook');
    }
}

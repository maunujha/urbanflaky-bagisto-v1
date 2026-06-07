<?php

declare(strict_types=1);

namespace Gabha\Blog\Providers;

use Gabha\Blog\ViewComposers\HomeBlogComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Root service provider for the Gabha Blog package.
 *
 * Wires the blog module into Bagisto purely through Laravel's extension points
 * (config merge, route / view / migration / translation loaders and a single
 * view composer) — no Bagisto core files are modified by this provider.
 */
class BlogServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');

        $this->loadRoutesFrom(__DIR__.'/../Routes/shop-routes.php');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'blog');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'blog');

        /*
         * Expose the latest published posts to the storefront home page as
         * `$latestBlogs` so the home grid renders without touching the core
         * HomeController.
         */
        View::composer('shop::home.index', HomeBlogComposer::class);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/menu.php',
            'menu.admin'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/acl.php',
            'acl'
        );
    }
}

<?php

use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\RedirectLegacyUrls;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Cookie\Middleware\EncryptCookies as BaseEncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Webkul\Core\Http\Middleware\SecureHeaders;
use Webkul\Installer\Http\Middleware\CanInstall;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        /**
         * Remove the default Laravel middleware that prevents requests during maintenance mode. There are three
         * middlewares in the shop that need to be loaded before this middleware. Therefore, we need to remove this
         * middleware from the list and add the overridden middleware at the end of the list.
         *
         * As of now, this has been added in the Admin and Shop providers. I will look for a better approach in Laravel 11 for this.
         */
        $middleware->remove(PreventRequestsDuringMaintenance::class);

        /**
         * Remove the default Laravel middleware that converts empty strings to null. First, handle all nullable cases,
         * then remove this line.
         */
        $middleware->remove(ConvertEmptyStringsToNull::class);

        /* Old Shopify URLs → current pages (301), before routing can 404 them. */
        $middleware->prepend(RedirectLegacyUrls::class);

        $middleware->append(SecureHeaders::class);
        $middleware->append(CanInstall::class);

        /**
         * Add the overridden middleware at the end of the list.
         */
        $middleware->replaceInGroup('web', BaseEncryptCookies::class, EncryptCookies::class);

        /*
         * Trust only the proxies listed in TRUSTED_PROXIES (comma-separated).
         * Trusting '*' lets any client spoof its IP via X-Forwarded-For, which
         * defeats every IP-keyed rate limiter (OTP, auth, API).
         */
        $middleware->trustProxies(at: env('TRUSTED_PROXIES'));
    })
    ->withSchedule(function (Schedule $schedule) {
        /* Shiprocket safety net: retry missed pushes, assign couriers, poll
           tracking for shipments whose webhook went quiet. */
        $schedule->command('shiprocket:sync')->everyThirtyMinutes()->withoutOverlapping(25);

        /* Reset search term use counts weekly to keep trending fresh */
        $schedule->command('search:reset-trending')->weekly();
    })
    ->withExceptions(function (Exceptions $exceptions) {})->create();

<?php

namespace App\Providers;

use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $allowedIPs = array_map('trim', explode(',', config('app.debug_allowed_ips', '')));

        $allowedIPs = array_filter($allowedIPs);

        if (empty($allowedIPs)) {
            return;
        }

        if (in_array(Request::ip(), $allowedIPs)) {
            Debugbar::enable();
        } else {
            Debugbar::disable();
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ParallelTesting::setUpTestDatabase(function (string $database, int $token) {
            Artisan::call('db:seed');
        });

        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        // General API — public catalog browsing, cart, wishlist
        RateLimiter::for('api', function ($request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        // Auth endpoints — login, register, forgot/reset password
        RateLimiter::for('api-auth', function ($request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // OTP endpoints — protects SMS costs and prevents brute-force verification
        RateLimiter::for('api-otp', function ($request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}

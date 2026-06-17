<?php

namespace App\Providers;

use App\Support\DataLayer;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
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

        $this->registerAnalyticsEvents();
    }

    /**
     * Queue GA4 login / sign_up data-layer events for the redirect that follows.
     *
     * All login paths (password, OTP, Google) dispatch `customer.after.login`,
     * and every registration dispatches `customer.registration.after`. Listening
     * centrally here covers every entry point without touching each controller.
     */
    protected function registerAnalyticsEvents(): void
    {
        Event::listen('customer.registration.after', function () {
            DataLayer::flash(['event' => 'sign_up']);
        });

        Event::listen('customer.after.login', function () {
            // Registration also logs the customer in this same request — if a
            // sign_up was already queued, don't double-count it as a login.
            foreach (session()->get('datalayer_events', []) as $queued) {
                if (($queued['event'] ?? null) === 'sign_up') {
                    return;
                }
            }

            DataLayer::flash(['event' => 'login']);
        });
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

        // OTP endpoints — protects SMS costs and prevents brute-force verification.
        // Layered limits: an IP cap stops one source flooding, while per-phone caps
        // stop a number being SMS-bombed from rotating IPs and bound the daily spend.
        RateLimiter::for('api-otp', function ($request) {
            $phone = (string) $request->input('phone');

            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(3)->by('otp-phone:'.$phone),
                Limit::perDay(15)->by('otp-phone:'.$phone),
            ];
        });

        // Payment order-creation — each hit creates a Razorpay order via their API.
        // Cap it so a script can't spam order->create (pollutes orders, can trip
        // Razorpay's fraud monitoring). Keyed by customer when known, else IP.
        RateLimiter::for('checkout', function ($request) {
            return Limit::perMinute(15)->by($request->user('customer')?->id ?: $request->ip());
        });
    }
}

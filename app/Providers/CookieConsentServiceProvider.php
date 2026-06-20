<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CookieConsentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        /* Inject the "Cookie Consent Manager" toggle into the admin
           Configuration → General → GDPR section. */
        $this->mergeConfigFrom(
            base_path('config/cookie-consent-system.php'),
            'core'
        );
    }
}

<?php

/*
|--------------------------------------------------------------------------
| OTP / public-form abuse protection
|--------------------------------------------------------------------------
|
| All counters live in Redis (atomic Lua scripts) so they are shared by every
| php-fpm and queue worker and survive application restarts. Day windows are
| rolling 24h from the first hit, except the site-wide ceilings, which reset
| on the calendar day in the app timezone (Asia/Kolkata).
|
*/

return [

    /* Redis connection holding the counters (config/database.php → redis). */
    'redis_connection' => env('OTP_REDIS_CONNECTION', 'default'),

    /* Namespace for every key written by these guards. */
    'key_prefix' => env('OTP_KEY_PREFIX', 'uf:sec:'),

    /* OTP length is fixed at 6 by the DLT template; expiry follows SMS copy. */
    'expiry_minutes' => (int) env('OTP_EXPIRY_MINUTES', 10),

    'send' => [
        'per_phone_window_max'     => (int) env('OTP_SEND_PER_PHONE_WINDOW_MAX', 3),
        'per_phone_window_seconds' => (int) env('OTP_SEND_PER_PHONE_WINDOW_SECONDS', 600),
        'per_phone_daily_max'      => (int) env('OTP_SEND_PER_PHONE_DAILY_MAX', 10),

        /* Shared across contact + checkout: one source can't farm both. */
        'per_ip_daily_max'         => (int) env('OTP_SEND_PER_IP_DAILY_MAX', 20),

        /*
         * Cost circuit-breaker. Contact, login and signup share a sub-ceiling
         * (contact also has its own) so no attack on them can consume the
         * checkout share: checkout always keeps site − non_checkout.
         */
        'site_daily_max'              => (int) env('OTP_SITE_DAILY_CEILING', 300),
        'site_daily_max_non_checkout' => (int) env('OTP_SITE_DAILY_CEILING_NON_CHECKOUT', 150),
        'site_daily_max_contact'      => (int) env('OTP_SITE_DAILY_CEILING_CONTACT', 100),
    ],

    'verify' => [
        'max_attempts_per_challenge' => (int) env('OTP_VERIFY_MAX_ATTEMPTS', 5),
        'per_ip_window_max'          => (int) env('OTP_VERIFY_PER_IP_WINDOW_MAX', 30),
        'per_ip_window_seconds'      => (int) env('OTP_VERIFY_PER_IP_WINDOW_SECONDS', 600),
    ],

    'newsletter' => [
        'per_ip_minute_max' => (int) env('NEWSLETTER_PER_IP_MINUTE_MAX', 5),
        'per_ip_daily_max'  => (int) env('NEWSLETTER_PER_IP_DAILY_MAX', 20),
        'per_email_daily_max' => (int) env('NEWSLETTER_PER_EMAIL_DAILY_MAX', 3),
    ],

    'recaptcha' => [
        /* Falls back to the admin-configured threshold when unset. */
        'min_score' => env('RECAPTCHA_MIN_SCORE'),

        /* Comma list; defaults to APP_URL's host and its www. variant. */
        'allowed_hostnames' => env('RECAPTCHA_ALLOWED_HOSTNAMES'),

        /*
         * Only when Google itself can't be reached (timeout/5xx). Invalid,
         * expired, reused, low-score or wrong-action tokens are ALWAYS rejected.
         */
        'fail_open_on_transport_error' => (bool) env('RECAPTCHA_FAIL_OPEN_ON_TRANSPORT_ERROR', true),

        'timeout_seconds' => (int) env('RECAPTCHA_TIMEOUT_SECONDS', 5),
    ],
];

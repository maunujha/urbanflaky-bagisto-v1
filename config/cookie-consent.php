<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Consent Version
    |--------------------------------------------------------------------------
    | Bump this (or set COOKIE_CONSENT_VERSION in .env) whenever the cookie
    | policy materially changes. A stored consent whose version differs from
    | this value is treated as invalid, so the banner re-appears and users are
    | forced to review their choices again.
    */
    'version' => env('COOKIE_CONSENT_VERSION', '1.0'),

    /*
    |--------------------------------------------------------------------------
    | Consent Validity (months)
    |--------------------------------------------------------------------------
    | How long a stored consent stays valid before the banner is shown again.
    */
    'validity_months' => (int) env('COOKIE_CONSENT_VALIDITY_MONTHS', 6),

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    | `locked` categories (essential) are always granted and cannot be toggled.
    | Display copy lives in the blade so it can use the store's voice/branding.
    */
    'categories' => [
        'essential'   => ['locked' => true],
        'analytics'   => ['locked' => false],
        'marketing'   => ['locked' => false],
        'preferences' => ['locked' => false],
    ],
];

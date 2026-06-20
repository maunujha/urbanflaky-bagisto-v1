<?php

/*
| Adds a dedicated admin Configuration field:
|   Admin → Configuration → General → GDPR → "Cookie Consent Manager"
|
| This is the master on/off switch for the whole consent layer. When OFF, the
| banner is not rendered and tracking scripts (GTM / GA4 / Clarity / Meta) load
| unconditionally exactly as they did before — no consent guard. Merged into
| Bagisto's `core` system config by CookieConsentServiceProvider.
*/

return [
    [
        'key'  => 'general.gdpr.consent_manager',
        'name' => 'Cookie Consent Manager',
        'info' => 'Premium GDPR cookie consent banner with granular categories (analytics, marketing, preferences) and conditional script loading for GA4 / GTM / Meta Pixel.',
        'sort' => 4,
        'fields' => [
            [
                'name'          => 'enable',
                'title'         => 'Enable Cookie Consent Manager',
                'info'          => 'When disabled, tracking scripts load unconditionally without any consent guard.',
                'type'          => 'boolean',
                'channel_based' => true,
                'locale_based'  => false,
            ],
        ],
    ],
];

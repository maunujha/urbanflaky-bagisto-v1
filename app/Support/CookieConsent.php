<?php

namespace App\Support;

use App\Models\CookieConsent as CookieConsentModel;

/**
 * Central source of truth for the cookie consent system.
 *
 * `enabled()` reads the dedicated admin switch
 * (Admin → Configuration → General → GDPR → Cookie Consent Manager). When it
 * returns false the whole consent layer is bypassed and tracking scripts load
 * exactly as before — no guard.
 */
class CookieConsent
{
    /**
     * Is the consent layer switched on in the admin?
     */
    public static function enabled(): bool
    {
        return (bool) core()->getConfigData('general.gdpr.consent_manager.enable');
    }

    /**
     * Current policy version — bumping it forces every visitor to re-consent.
     */
    public static function version(): string
    {
        return (string) config('cookie-consent.version', '1.0');
    }

    /**
     * How long a stored consent stays valid (in months).
     */
    public static function validityMonths(): int
    {
        return (int) config('cookie-consent.validity_months', 6);
    }

    /**
     * Configured categories metadata (keys + locked flags).
     */
    public static function categories(): array
    {
        return (array) config('cookie-consent.categories', []);
    }

    /**
     * The stored consent for a logged-in customer (or null), shaped for the
     * front-end bootstrap so it can seed localStorage on a fresh device.
     */
    public static function forUser($user): ?array
    {
        if (! $user) {
            return null;
        }

        $row = CookieConsentModel::query()
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'essential'   => true,
            'analytics'   => (bool) $row->analytics,
            'marketing'   => (bool) $row->marketing,
            'preferences' => (bool) $row->preferences,
            'version'     => (string) $row->consent_version,
            'timestamp'   => optional($row->updated_at)->toIso8601String(),
        ];
    }
}

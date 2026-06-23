<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Central source of truth for the exit-intent welcome discount popup.
 *
 * `enabled()` reads the dedicated admin switch
 * (Admin → Marketing → Exit Intent Popup). When it returns false the popup
 * component renders nothing and no listeners are attached.
 */
class ExitIntentPopup
{
    public static function enabled(): bool
    {
        return (bool) core()->getConfigData('marketing.exit_intent_popup.settings.enabled');
    }

    /**
     * Resolved settings, with sane defaults and image paths turned into URLs,
     * ready to hand straight to the blade component as props.
     */
    public static function settings(): array
    {
        return [
            'title'              => core()->getConfigData('marketing.exit_intent_popup.settings.title') ?: 'WAIT BEFORE YOU GO',
            'description'        => core()->getConfigData('marketing.exit_intent_popup.settings.description') ?: 'Discover premium oversized streetwear crafted for those who prefer a darker, minimalist aesthetic.',
            'couponCode'         => core()->getConfigData('marketing.exit_intent_popup.settings.coupon_code') ?: 'WELCOME10',
            'discountPercentage' => core()->getConfigData('marketing.exit_intent_popup.settings.discount_percentage') ?: 10,
            'ctaText'            => core()->getConfigData('marketing.exit_intent_popup.settings.cta_text') ?: 'CLAIM MY DISCOUNT',
            'desktopImage'       => self::imageUrl('marketing.exit_intent_popup.settings.desktop_image'),
            'mobileImage'        => self::imageUrl('marketing.exit_intent_popup.settings.mobile_image'),
            'frequencyDays'      => (int) (core()->getConfigData('marketing.exit_intent_popup.settings.frequency_days') ?: 7),
        ];
    }

    private static function imageUrl(string $configKey): ?string
    {
        $path = core()->getConfigData($configKey);

        return $path ? Storage::url($path) : null;
    }
}

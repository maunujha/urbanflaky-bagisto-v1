<?php

namespace Gabha\Notifications\Services;

use Webkul\CartRule\Models\CartRuleCoupon;

/**
 * The welcome offer shown in the account welcome and newsletter emails.
 * Shown only while its coupon really works: an unknown, expired, used-up or
 * switched-off coupon means no offer at all, never a dead code.
 */
class WelcomeOffer
{
    public function __construct(protected AutomationSettings $settings) {}

    /**
     * @return array{code: string, text: string}|null
     */
    public function current(): ?array
    {
        if (! $this->settings->enabled('welcome.offer_enabled')) {
            return null;
        }

        $code = $this->settings->text('welcome.offer_coupon');

        if ($code === '' || ! $this->usable($code)) {
            return null;
        }

        return [
            'code' => $code,
            'text' => $this->settings->text('welcome.offer_text'),
        ];
    }

    protected function usable(string $code): bool
    {
        $coupon = CartRuleCoupon::with('cart_rule')->where('code', $code)->first();
        $rule = $coupon?->cart_rule;
        $today = now()->toDateString();

        /* Dates may be Carbon or "Y-m-d[ H:i:s]" strings: compare the date part. */
        $date = fn ($value) => $value ? substr((string) $value, 0, 10) : null;

        return $coupon
            && $rule
            && $rule->status
            && (! $date($rule->starts_from) || $date($rule->starts_from) <= $today)
            && (! $date($rule->ends_till) || $date($rule->ends_till) >= $today)
            && (! $date($coupon->expired_at) || $date($coupon->expired_at) >= $today)
            && (! $coupon->usage_limit || $coupon->times_used < $coupon->usage_limit);
    }
}

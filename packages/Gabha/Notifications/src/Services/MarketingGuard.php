<?php

namespace Gabha\Notifications\Services;

use Gabha\Notifications\Models\EmailLog;
use Gabha\Notifications\Models\EmailSuppression;
use Webkul\Core\Models\SubscribersList;

/**
 * Who may receive marketing email, and how much of it the store may send.
 * Consent is the newsletter opt-in; its token powers the unsubscribe link.
 */
class MarketingGuard
{
    public function subscription(string $email): ?SubscribersList
    {
        return SubscribersList::where('email', $email)->where('is_subscribed', 1)->first();
    }

    /**
     * Why this address cannot get a marketing email right now (null: it can).
     */
    public function blockedReason(string $email): ?string
    {
        if (! $this->subscription($email)) {
            return 'not subscribed';
        }

        if (EmailSuppression::isSuppressed($email, EmailSuppression::SCOPE_MARKETING)) {
            return 'unsubscribed';
        }

        $gap = (int) config('notifications.limits.marketing_gap_hours', 48);

        $recent = EmailLog::where('recipient', mb_strtolower($email))
            ->where('category', EmailLog::CATEGORY_MARKETING)
            ->where('status', '!=', EmailLog::STATUS_FAILED)
            ->where('created_at', '>=', now()->subHours($gap))
            ->exists();

        return $recent ? "emailed in the last {$gap}h" : null;
    }

    /**
     * Marketing emails still allowed today (store timezone).
     */
    public function remainingToday(): int
    {
        $sent = EmailLog::where('category', EmailLog::CATEGORY_MARKETING)
            ->where('status', '!=', EmailLog::STATUS_FAILED)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        return max(0, (int) config('notifications.limits.marketing_daily_cap', 100) - $sent);
    }
}

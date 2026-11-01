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
     *
     * @param  string|null  $sequence  dedupe-key prefix of the sequence being
     *                                 sent (one bag's reminders): its own earlier
     *                                 steps do not count against the gap
     */
    public function blockedReason(string $email, ?string $sequence = null): ?string
    {
        if (! EmailDispatcher::deliverable($email)) {
            return 'no deliverable address';
        }

        if (! $this->subscription($email)) {
            return 'not subscribed';
        }

        if (EmailSuppression::isSuppressed($email, EmailSuppression::SCOPE_MARKETING)) {
            return 'unsubscribed';
        }

        $gap = (int) config('notifications.limits.marketing_gap_hours', 48);

        $recent = EmailLog::where('recipient', mb_strtolower($email))
            ->whereIn('category', [EmailLog::CATEGORY_MARKETING, EmailLog::CATEGORY_CAMPAIGN])
            ->whereIn('status', EmailLog::OUTGOING)
            ->where('created_at', '>=', now()->subHours($gap))
            ->when($sequence, fn ($query) => $query->where(
                fn ($query) => $query->whereNull('dedupe_key')->orWhere('dedupe_key', 'not like', $sequence.'%')
            ))
            ->exists();

        return $recent ? "emailed in the last {$gap}h" : null;
    }

    /**
     * Emails of one category still allowed today (store timezone). Campaigns
     * have their own cap so a big send never starves the bag reminders.
     */
    public function remainingToday(string $category = EmailLog::CATEGORY_MARKETING): int
    {
        $cap = $category === EmailLog::CATEGORY_CAMPAIGN ? 'campaign_daily_cap' : 'marketing_daily_cap';

        $sent = EmailLog::where('category', $category)
            ->whereIn('status', EmailLog::OUTGOING)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        return max(0, (int) config('notifications.limits.'.$cap, 100) - $sent);
    }
}

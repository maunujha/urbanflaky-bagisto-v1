<?php

namespace Gabha\Notifications\Services;

use Gabha\Notifications\Mail\CampaignMail;
use Gabha\Notifications\Models\EmailLog;
use Gabha\Notifications\Models\EmailSuppression;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Webkul\Core\Models\SubscribersList;
use Webkul\Marketing\Helpers\Campaign;

/**
 * Replaces Bagisto's campaign sender (bound in its place, so its daily
 * `campaign:process` now runs this). Bagisto's version re-sent undated
 * campaigns to everyone every day, mailed unsubscribed guests, and had no
 * unsubscribe link or limit. Here:
 *
 * - recipients are active newsletter subscribers only (the guest group: those
 *   without an account; other groups: their customers who subscribed);
 * - each campaign reaches each address once (once a year for birthdays);
 * - a dated campaign sends on its date and, if the daily cap cut it short,
 *   carries on for a few days; an undated one goes out once, paced by the cap;
 * - every email has a one-click unsubscribe.
 */
class SafeCampaignSender extends Campaign
{
    public function process(): void
    {
        $settings = app(AutomationSettings::class);

        /* Bagisto schedules campaign:process itself: honour the same off-switch as our commands. */
        if (! config('notifications.scheduled') && ! app()->runningUnitTests()) {
            return;
        }

        if (! $settings->enabled('lifecycle.campaigns')) {
            return;
        }

        $guard = app(MarketingGuard::class);
        $dispatcher = app(EmailDispatcher::class);
        $budget = $guard->remainingToday(EmailLog::CATEGORY_CAMPAIGN);

        foreach ($this->dueCampaigns() as $campaign) {
            $birthday = $campaign->event?->name === 'Birthday';
            $round = $birthday ? now()->year : ($campaign->event?->date ? Carbon::parse($campaign->event->date)->toDateString() : 'once');

            foreach ($birthday ? $this->getBirthdayEmails($campaign) : $this->getEmailAddresses($campaign) as $email) {
                if ($budget <= 0) {
                    return;
                }

                $email = mb_strtolower(trim($email));
                $key = "campaign:{$campaign->id}:{$round}:{$email}";

                /* Opted-out addresses go on to the dispatcher, which withholds and logs them. */
                if ($dispatcher->alreadySent($key) || ! EmailDispatcher::deliverable($email)) {
                    continue;
                }

                $unsubscribe = URL::signedRoute('shop.notifications.unsubscribe', [
                    'email' => $email,
                    'scope' => EmailSuppression::SCOPE_MARKETING,
                ]);

                if ($dispatcher->send(new CampaignMail($email, $campaign, $unsubscribe), 'campaign.'.$campaign->id, $key, $campaign, EmailLog::CATEGORY_CAMPAIGN)) {
                    $budget--;
                }
            }
        }
    }

    /**
     * Active campaigns whose date is today or a few days back (to finish a
     * capped send), plus undated ones.
     */
    protected function dueCampaigns()
    {
        $spillover = (int) config('notifications.campaigns.spillover_days', 3);

        return $this->campaignRepository->getModel()
            ->with(['event', 'email_template', 'customer_group'])
            ->leftJoin('marketing_events', 'marketing_campaigns.marketing_event_id', 'marketing_events.id')
            ->leftJoin('marketing_templates', 'marketing_campaigns.marketing_template_id', 'marketing_templates.id')
            ->select('marketing_campaigns.*')
            ->where('marketing_campaigns.status', 1)
            ->where('marketing_templates.status', 'active')
            ->where(function ($query) use ($spillover) {
                $query->whereNull('marketing_events.date')
                    ->orWhereBetween('marketing_events.date', [now()->subDays($spillover)->toDateString(), now()->toDateString()])
                    ->orWhere('marketing_events.name', 'Birthday');
            })
            ->get();
    }

    /**
     * Subscribed addresses only, whatever the group.
     *
     * @return array<int, string>
     */
    public function getEmailAddresses($campaign)
    {
        $subscribers = SubscribersList::where('is_subscribed', 1);

        if ($campaign->customer_group?->code === 'guest') {
            $subscribers->whereNull('customer_id');
        } else {
            $subscribers->whereIn('customer_id', $campaign->customer_group->customers()->select('customers.id'));
        }

        return array_values(array_unique($subscribers->pluck('email')->all()));
    }

    /**
     * @return array<int, string>
     */
    public function getBirthdayEmails($campaign)
    {
        $subscribed = SubscribersList::where('is_subscribed', 1)->pluck('email')->map(fn ($e) => mb_strtolower($e))->all();

        return array_values(array_intersect(
            array_map('mb_strtolower', parent::getBirthdayEmails($campaign)),
            $subscribed,
        ));
    }
}

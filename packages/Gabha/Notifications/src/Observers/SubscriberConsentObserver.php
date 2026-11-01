<?php

namespace Gabha\Notifications\Observers;

use Gabha\Notifications\Models\EmailSuppression;
use Gabha\Notifications\Models\MarketingConsent;
use Illuminate\Database\Eloquent\Model;

/**
 * Logs every newsletter consent change, whichever path made it: footer form,
 * account settings, sign-up, unsubscribe links, double opt-in or admin.
 */
class SubscriberConsentObserver
{
    public function created(Model $subscriber): void
    {
        if ($subscriber->is_subscribed) {
            $this->log($subscriber, MarketingConsent::GRANTED);
        }
    }

    public function updated(Model $subscriber): void
    {
        if (! $subscriber->wasChanged('is_subscribed')) {
            return;
        }

        $this->log($subscriber, $subscriber->is_subscribed ? MarketingConsent::GRANTED : MarketingConsent::WITHDRAWN);
    }

    public function deleted(Model $subscriber): void
    {
        if ($subscriber->getOriginal('is_subscribed')) {
            $this->log($subscriber, MarketingConsent::WITHDRAWN);
        }
    }

    protected function log(Model $subscriber, string $action): void
    {
        try {
            MarketingConsent::record($subscriber->email, $action, $subscriber->customer_id);

            /* A fresh opt-in is the newest consent: it lifts an earlier unsubscribe. */
            if ($action === MarketingConsent::GRANTED) {
                EmailSuppression::where('email', mb_strtolower(trim($subscriber->email)))
                    ->where('scope', EmailSuppression::SCOPE_MARKETING)
                    ->delete();
            }
        } catch (\Throwable $e) {
            /* The ledger must never block a subscribe or unsubscribe. */
            report($e);
        }
    }
}

<?php

namespace Gabha\Notifications\Listeners;

use Gabha\Notifications\Mail\SubscriptionConfirmation;
use Gabha\Notifications\Models\MarketingConsent;
use Gabha\Notifications\Services\EmailDispatcher;
use Illuminate\Support\Facades\URL;

/**
 * Double opt-in: emails the confirmation link when the footer form asks for it.
 */
class SubscriptionEmails
{
    public function __construct(protected EmailDispatcher $dispatcher) {}

    public function confirmationRequested($subscriber): void
    {
        try {
            /* The token rotates on every request, so only the newest link confirms. */
            $url = URL::temporarySignedRoute('shop.notifications.subscription.confirm', now()->addDays(7), [
                'subscriber' => $subscriber->id,
                'token'      => $subscriber->token,
            ]);

            if ($this->dispatcher->send(
                new SubscriptionConfirmation($subscriber, $url),
                'newsletter.confirm',
                'newsletter.confirm:'.$subscriber->token,
                $subscriber,
            )) {
                MarketingConsent::record($subscriber->email, MarketingConsent::CONFIRMATION_SENT, $subscriber->customer_id);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

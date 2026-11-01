<?php

namespace Gabha\Notifications\Mail;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Core\Contracts\SubscribersList;

/**
 * Double opt-in: the address joins the list only once this link is clicked.
 */
class SubscriptionConfirmation extends BrandMailable
{
    public function __construct(public SubscribersList $subscriber, public string $confirmUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->subscriber->email)],
            subject: trans('notifications::app.emails.confirm-subscription.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'notifications::emails.confirm-subscription',
            text: 'notifications::emails.confirm-subscription-text',
        );
    }
}

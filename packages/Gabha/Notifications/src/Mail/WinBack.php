<?php

namespace Gabha\Notifications\Mail;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Customer\Contracts\Customer;

/**
 * For a past customer who has not ordered in a while: what is new, no discount
 * promised.
 */
class WinBack extends BrandMailable
{
    public function __construct(public Customer $customer, ?string $unsubscribeUrl = null)
    {
        $this->unsubscribeUrl = $unsubscribeUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->customer->email, $this->customer->name)],
            subject: trans('notifications::app.emails.win-back.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'notifications::emails.win-back',
            text: 'notifications::emails.win-back-text',
            with: ['shopUrl' => route('shop.home.index')],
        );
    }
}

<?php

namespace Gabha\Notifications\Mail;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Marketing\Contracts\Campaign;

/**
 * An admin campaign (Marketing → Communications → Campaigns): the template's
 * HTML inside the store's email layout, with an unsubscribe link and header.
 */
class CampaignMail extends BrandMailable
{
    public function __construct(public string $email, public Campaign $campaign, ?string $unsubscribeUrl = null)
    {
        $this->unsubscribeUrl = $unsubscribeUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->email)],
            subject: $this->campaign->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'notifications::emails.campaign',
            with: ['html' => $this->campaign->email_template?->content ?? ''],
        );
    }
}

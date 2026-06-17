<?php

namespace Webkul\Shop\Mail;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactUsAcknowledgement extends Mailable
{
    /**
     * Create a new message instance.
     *
     * @param  array  $contactUs  name, email, contact, topic, message
     * @return void
     */
    public function __construct(public array $contactUs) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address($this->contactUs['email'], $this->contactUs['name'] ?? null),
            ],
            subject: trans('shop::app.emails.contact-us.ack-subject'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'shop::emails.contact-us-ack',
            text: 'shop::emails.contact-us-ack-text',
        );
    }
}

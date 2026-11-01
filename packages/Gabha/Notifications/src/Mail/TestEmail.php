<?php

namespace Gabha\Notifications\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * A test send from Email Templates: an already-rendered email delivered to
 * the tester only. The original mailable is never sent, so its real
 * recipient (the customer behind the sample order) can never get a copy.
 * Sent immediately, not queued: the admin sees the result straight away.
 */
class TestEmail extends Mailable
{
    public function __construct(
        public string $recipient,
        public string $subjectLine,
        public string $htmlBody,
        public ?string $plain = null,
    ) {}

    public function envelope(): Envelope
    {
        $sender = core()->getSenderEmailDetails();

        return new Envelope(
            from: new Address($sender['email'], $sender['name']),
            to: [new Address($this->recipient)],
            subject: '[Test] '.$this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->htmlBody,
            text: $this->plain ? 'notifications::emails.raw-text' : null,
            with: ['body' => $this->plain],
        );
    }
}

<?php

namespace Gabha\Notifications\Mail;

use Gabha\Notifications\Mail\Concerns\TracksDelivery;
use Illuminate\Mail\Mailables\Headers;
use Webkul\Shop\Mail\Mailable;

/**
 * Base for the automation emails: store sender, delivery tracking and, when
 * an opt-out link exists, RFC 8058 one-click unsubscribe headers.
 */
abstract class BrandMailable extends Mailable
{
    use TracksDelivery;

    /** Signed opt-out URL; null for emails that cannot be opted out of. */
    public ?string $unsubscribeUrl = null;

    public function headers(): Headers
    {
        $text = $this->trackingHeaders();

        if ($this->unsubscribeUrl) {
            $text['List-Unsubscribe'] = '<'.$this->unsubscribeUrl.'>';
            $text['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
        }

        return new Headers(text: $text);
    }
}

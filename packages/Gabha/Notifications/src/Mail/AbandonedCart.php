<?php

namespace Gabha\Notifications\Mail;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Checkout\Contracts\Cart;

/**
 * Reminder for a checkout left unfinished. When the customer already chose an
 * online payment method, the copy speaks to a payment that did not go through.
 */
class AbandonedCart extends BrandMailable
{
    public function __construct(
        public Cart $cart,
        public int $step,
        public string $recoverUrl,
        ?string $unsubscribeUrl = null,
    ) {
        $this->unsubscribeUrl = $unsubscribeUrl;
    }

    public function paymentFailed(): bool
    {
        $method = $this->cart->payment?->method;

        return $method && $method !== 'cashondelivery';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->cart->customer_email, trim($this->cart->customer_first_name.' '.$this->cart->customer_last_name))],
            subject: trans($this->copyKey().'.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'notifications::emails.abandoned-cart',
            text: 'notifications::emails.abandoned-cart-text',
            with: [
                'copy'  => $this->copyKey(),
                'items' => $this->cart->items->whereNull('parent_id')->values(),
            ],
        );
    }

    protected function copyKey(): string
    {
        $variant = $this->paymentFailed() ? 'payment' : 'step'.$this->step;

        return 'notifications::app.emails.cart.'.$variant;
    }
}

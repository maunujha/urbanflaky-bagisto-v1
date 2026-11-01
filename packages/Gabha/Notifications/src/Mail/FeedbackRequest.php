<?php

namespace Gabha\Notifications\Mail;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\URL;
use Webkul\Sales\Contracts\Order;

/**
 * One-tap 1–5 rating. Each link opens a signed page with that rating chosen;
 * nothing is saved until the customer presses Send, so mail scanners that
 * open every link record nothing.
 */
class FeedbackRequest extends BrandMailable
{
    public function __construct(public Order $order, ?string $unsubscribeUrl = null)
    {
        $this->unsubscribeUrl = $unsubscribeUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->order->customer_email, $this->order->customer_full_name)],
            subject: trans('notifications::app.emails.feedback.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'notifications::emails.feedback-request',
            text: 'notifications::emails.feedback-request-text',
            with: ['ratings' => $this->ratingUrls()],
        );
    }

    /**
     * @return array<int, string> rating => signed URL
     */
    public function ratingUrls(): array
    {
        $urls = [];

        foreach (range(1, 5) as $rating) {
            $urls[$rating] = URL::temporarySignedRoute('shop.notifications.feedback.show', now()->addDays(30), [
                'orderId' => $this->order->id,
                'rating' => $rating,
            ]);
        }

        return $urls;
    }
}

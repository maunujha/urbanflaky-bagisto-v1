<?php

namespace Gabha\Notifications\Mail;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Sales\Contracts\Order;

/**
 * A few days after delivery: ask for a review of each piece in the order.
 */
class ReviewRequest extends BrandMailable
{
    public function __construct(public Order $order, ?string $unsubscribeUrl = null)
    {
        $this->unsubscribeUrl = $unsubscribeUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->order->customer_email, $this->order->customer_full_name)],
            subject: trans('notifications::app.emails.review.subject'),
        );
    }

    public function content(): Content
    {
        $products = $this->order->items
            ->whereNull('parent_id')
            ->map(fn ($item) => [
                'name' => $item->name,
                'url'  => $item->product?->url_key
                    ? route('shop.product_or_category.index', $item->product->url_key).'#review'
                    : null,
            ])
            ->filter(fn ($product) => $product['url'])
            ->unique('url')
            ->values();

        return new Content(
            view: 'notifications::emails.review-request',
            text: 'notifications::emails.review-request-text',
            with: ['products' => $products],
        );
    }
}

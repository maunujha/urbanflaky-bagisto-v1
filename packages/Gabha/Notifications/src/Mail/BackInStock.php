<?php

namespace Gabha\Notifications\Mail;

use Gabha\Notifications\Models\StockAlert;
use Gabha\Notifications\Support\ProductPresenter;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The piece a customer asked about is back. Sent once per request.
 */
class BackInStock extends BrandMailable
{
    public function __construct(public StockAlert $alert) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->alert->email)],
            subject: trans('notifications::app.emails.back-in-stock.subject', [
                'product' => ProductPresenter::name($this->alert->product),
            ]),
        );
    }

    public function content(): Content
    {
        $product = $this->alert->product;

        return new Content(
            view: 'notifications::emails.back-in-stock',
            text: 'notifications::emails.back-in-stock-text',
            with: [
                'name'    => ProductPresenter::name($product),
                'options' => ProductPresenter::optionLabel($product),
                'image'   => ProductPresenter::imageUrl($product),
                'url'     => ProductPresenter::url($product),
                'price'   => core()->currency($product->getTypeInstance()->getMinimalPrice()),
            ],
        );
    }
}

<?php

namespace Gabha\Notifications\Mail;

use Gabha\Notifications\Support\ProductPresenter;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Checkout\Contracts\Cart;

/**
 * Reminder for a checkout left unfinished, showing the real bag: picture,
 * size, quantity and price of each piece. When the customer already chose an
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

        return $method && ! in_array($method, config('notifications.offline_payment_methods', []), true);
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
                'items' => $this->items(),
                'total' => core()->formatPrice($this->cart->grand_total, $this->cart->cart_currency_code),
            ],
        );
    }

    /**
     * @return array<int, array{name: string, options: string, image: ?string, qty: int, price: string, total: string}>
     */
    public function items(): array
    {
        $currency = $this->cart->cart_currency_code;

        return $this->cart->items
            ->whereNull('parent_id')
            ->map(function ($item) use ($currency) {
                /* A configurable line's chosen variant is its child line. */
                $variant = $item->child?->product ?? $item->product;

                return [
                    'name'    => $item->name,
                    'options' => $variant ? ProductPresenter::optionLabel($variant) : '',
                    'image'   => ProductPresenter::imageUrl($variant ?? $item->product),
                    'qty'     => (int) $item->quantity,
                    'price'   => core()->formatPrice($item->price_incl_tax ?: $item->price, $currency),
                    'total'   => core()->formatPrice($item->total_incl_tax ?: $item->total, $currency),
                ];
            })
            ->values()
            ->all();
    }

    protected function copyKey(): string
    {
        $variant = $this->paymentFailed() ? 'payment' : 'step'.$this->step;

        return 'notifications::app.emails.cart.'.$variant;
    }
}

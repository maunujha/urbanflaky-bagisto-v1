<?php

namespace Gabha\Notifications\Mail;

use App\Models\ShiprocketOrder;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Sales\Contracts\Order;

/**
 * Courier progress after "shipped": out for delivery, delivered, a failed
 * delivery attempt, or the parcel returning to us. Copy is keyed by stage.
 */
class ShipmentUpdate extends BrandMailable
{
    public function __construct(public Order $order, public string $stage) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->order->customer_email, $this->order->customer_full_name)],
            subject: trans('notifications::app.emails.shipment.'.$this->stage.'.subject', [
                'order_id' => $this->order->increment_id,
            ]),
        );
    }

    public function content(): Content
    {
        $awb = ShiprocketOrder::where('order_id', $this->order->id)->value('awb_code');

        return new Content(
            view: 'notifications::emails.shipment-update',
            text: 'notifications::emails.shipment-update-text',
            with: [
                'copy'     => 'notifications::app.emails.shipment.'.$this->stage,
                'trackUrl' => $awb ? route('shop.track-order.index', ['awb' => $awb]) : null,
                'orderUrl' => route('shop.customers.account.orders.view', $this->order->id),
            ],
        );
    }
}

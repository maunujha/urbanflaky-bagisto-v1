<?php

namespace Gabha\Notifications\Mail;

use Gabha\Notifications\Mail\Concerns\TracksDelivery;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Throwable;
use Webkul\Core\Traits\PDFHandler;
use Webkul\Sales\Models\OrderTransaction;
use Webkul\Shop\Mail\Order\CreatedNotification;

/**
 * The one confirmation per order, worded by how it is paid:
 *
 * - Prepaid, payment verified: "payment received" with the gateway reference,
 *   and the invoice PDF attached (no separate invoice email).
 * - Cash on delivery: the amount to keep ready; never a payment claim and never
 *   an invoice, even if Bagisto raised one at checkout.
 *
 * "Verified" means a captured transaction, which the store records only after
 * Razorpay's signature check plus its API confirming the capture (or a
 * signature-verified webhook), never on the strength of a browser redirect.
 */
class OrderConfirmation extends CreatedNotification
{
    use PDFHandler, TracksDelivery;

    public const CAPTURED = 'captured';

    public function paymentTransaction(): ?OrderTransaction
    {
        if ($this->isOfflinePayment()) {
            return null;
        }

        return $this->order->transactions->firstWhere('status', self::CAPTURED);
    }

    public function isCashOnDelivery(): bool
    {
        return $this->order->payment?->method === 'cashondelivery';
    }

    public function isOfflinePayment(): bool
    {
        return in_array($this->order->payment?->method, config('notifications.offline_payment_methods', []), true);
    }

    public function envelope(): Envelope
    {
        $envelope = parent::envelope();

        $variant = match (true) {
            $this->paymentTransaction() !== null => 'paid',
            $this->isCashOnDelivery()            => 'cod',
            default                              => null,
        };

        if ($variant) {
            $envelope->subject = trans('notifications::app.emails.order.'.$variant.'-subject', [
                'order_id' => $this->order->increment_id,
            ]);
        }

        return $envelope;
    }

    public function headers(): Headers
    {
        return new Headers(text: $this->trackingHeaders());
    }

    public function content(): Content
    {
        $payment = $this->paymentTransaction();

        return new Content(
            view: 'shop::emails.orders.created',
            with: [
                'payment'         => $payment,
                'paymentTitle'    => $this->order->payment?->method_title,
                'isCod'           => $this->isCashOnDelivery(),
                'invoiceAttached' => $payment && $this->order->invoices->isNotEmpty(),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->paymentTransaction()) {
            return [];
        }

        $attachments = [];

        foreach ($this->order->invoices as $invoice) {
            try {
                $pdf = $this->generatePdf(view('shop::customers.account.orders.pdf', [
                    'invoice'           => $invoice,
                    'orderCurrencyCode' => $this->order->order_currency_code,
                ])->render());

                $attachments[] = Attachment::fromData(fn () => $pdf, 'invoice-'.$invoice->increment_id.'.pdf')
                    ->withMime('application/pdf');
            } catch (Throwable $e) {
                /* A broken PDF must never stop the confirmation itself. */
                report($e);
            }
        }

        return $attachments;
    }
}

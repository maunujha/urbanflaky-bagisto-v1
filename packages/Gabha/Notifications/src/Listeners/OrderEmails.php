<?php

namespace Gabha\Notifications\Listeners;

use Gabha\Notifications\Services\OrderConfirmationCoordinator;
use Webkul\Shop\Listeners\Invoice as ShopInvoiceListener;

/**
 * Replaces Bagisto's customer order-created and invoice-created emails (the
 * Shop listeners stay registered for every other event).
 */
class OrderEmails
{
    public function __construct(
        protected OrderConfirmationCoordinator $confirmations,
        protected ShopInvoiceListener $invoiceListener,
    ) {}

    public function orderPlaced($order): void
    {
        try {
            $this->confirmations->orderPlaced($order);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Prepaid checkout: the invoice rides on the confirmation. Any later
     * invoice (COD on delivery, admin) keeps its own email.
     */
    public function invoiceCreated($invoice): void
    {
        if ($this->confirmations->coversInvoiceOf((int) $invoice->order_id)) {
            return;
        }

        $this->invoiceListener->afterCreated($invoice);
    }
}

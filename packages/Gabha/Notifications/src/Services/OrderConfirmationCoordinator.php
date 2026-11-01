<?php

namespace Gabha\Notifications\Services;

use Gabha\Notifications\Mail\OrderConfirmation;
use Illuminate\Support\Facades\DB;
use Webkul\Sales\Contracts\Order as OrderContract;
use Webkul\Sales\Models\Order;

/**
 * Sends one order confirmation per order, after the request that placed it
 * has finished. A prepaid checkout creates the order and then its invoice in
 * the same request, so waiting until the end lets the confirmation carry the
 * invoice PDF, and the separate invoice email is skipped for that order.
 * Waiting also guarantees the order row is committed before the email is
 * queued, and that a rolled-back order sends nothing.
 */
class OrderConfirmationCoordinator
{
    /** @var array<int, ?string> order id => payment method, for orders placed in this request */
    protected array $pending = [];

    protected bool $flushRegistered = false;

    public function __construct(protected EmailDispatcher $dispatcher) {}

    public function orderPlaced(OrderContract $order): void
    {
        if (! core()->getConfigData('emails.general.notifications.emails.general.notifications.new_order')) {
            return;
        }

        $this->pending[$order->id] = $order->payment?->method;

        if ($this->flushRegistered) {
            return;
        }

        $this->flushRegistered = true;

        /* Queue workers and console commands never "terminate" per order. */
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            DB::afterCommit(fn () => $this->flush());

            return;
        }

        app()->terminating(fn () => $this->flush());
    }

    /**
     * Whether this invoice's order will get its confirmation with the invoice
     * attached, so the stand-alone invoice email is redundant. Only prepaid
     * orders: an invoice Bagisto raises for cash on delivery keeps its own
     * email and is never presented as a payment.
     */
    public function coversInvoiceOf(int $orderId): bool
    {
        if (! array_key_exists($orderId, $this->pending)) {
            return false;
        }

        $method = $this->pending[$orderId];

        return $method && ! in_array($method, config('notifications.offline_payment_methods', []), true);
    }

    public function flush(): void
    {
        $orderIds = array_keys($this->pending);

        $this->pending = [];
        $this->flushRegistered = false;

        foreach ($orderIds as $orderId) {
            /* Gone means the checkout transaction rolled back. */
            $order = Order::with(['invoices', 'transactions', 'payment'])->find($orderId);

            if (! $order) {
                continue;
            }

            try {
                $this->dispatcher->send(new OrderConfirmation($order), 'order.confirmation', 'order.confirmation:'.$order->id, $order);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}

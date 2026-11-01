<?php

use Gabha\Notifications\Mail\OrderConfirmation;
use Gabha\Notifications\Models\EmailLog;
use Gabha\Notifications\Services\OrderConfirmationCoordinator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Webkul\Sales\Models\Invoice;
use Webkul\Shop\Listeners\Invoice as ShopInvoiceListener;
use Webkul\Shop\Mail\Order\CreatedNotification;
use Webkul\Shop\Mail\Order\InvoicedNotification;

require_once __DIR__.'/Helpers.php';

beforeEach(function () {
    Mail::fake();

    bagistoMailSwitch('new_order');
    bagistoMailSwitch('new_invoice');
});

it('sends one confirmation with the invoice for a prepaid order, and no separate invoice email', function () {
    $order = notificationsOrder();

    Event::dispatch('checkout.order.save.after', $order);

    $invoice = Invoice::factory()->create(['order_id' => $order->id]);
    Event::dispatch('sales.invoice.save.after', $invoice);

    Mail::assertNotQueued(OrderConfirmation::class);

    app(OrderConfirmationCoordinator::class)->flush();

    Mail::assertQueued(OrderConfirmation::class, fn ($mail) => $mail->order->id === $order->id && $mail->order->invoices->count() === 1);
    Mail::assertQueued(OrderConfirmation::class, 1);
    Mail::assertNotQueued(InvoicedNotification::class);
});

it('never folds a cash-on-delivery invoice raised at checkout into the confirmation', function () {
    $order = notificationsOrder([], 'cashondelivery');

    Event::dispatch('checkout.order.save.after', $order);

    $invoice = Invoice::factory()->create(['order_id' => $order->id]);
    Event::dispatch('sales.invoice.save.after', $invoice);

    /* Bagisto's own invoice email, unchanged; not a payment confirmation. */
    Mail::assertQueued(InvoicedNotification::class, 1);

    app(OrderConfirmationCoordinator::class)->flush();

    Mail::assertQueued(OrderConfirmation::class, fn ($mail) => $mail->isCashOnDelivery() && $mail->paymentTransaction() === null);
});

it('confirms a cash-on-delivery order without an invoice, and emails the later invoice on its own', function () {
    $order = notificationsOrder([], 'cashondelivery');

    Event::dispatch('checkout.order.save.after', $order);
    app(OrderConfirmationCoordinator::class)->flush();

    Mail::assertQueued(OrderConfirmation::class, fn ($mail) => $mail->order->invoices->isEmpty());

    /* Delivered later: the COD invoice is a separate event, in a separate request. */
    $invoice = Invoice::factory()->create(['order_id' => $order->id]);
    Event::dispatch('sales.invoice.save.after', $invoice);

    Mail::assertQueued(InvoicedNotification::class, 1);
});

it('never sends Bagisto\'s own order-created email alongside it', function () {
    Event::dispatch('checkout.order.save.after', notificationsOrder());
    app(OrderConfirmationCoordinator::class)->flush();

    Mail::assertNotQueued(CreatedNotification::class, fn ($mail) => ! $mail instanceof OrderConfirmation);
});

it('confirms an order only once, however often the event fires', function () {
    $order = notificationsOrder();

    Event::dispatch('checkout.order.save.after', $order);
    app(OrderConfirmationCoordinator::class)->flush();

    Event::dispatch('checkout.order.save.after', $order);
    app(OrderConfirmationCoordinator::class)->flush();

    Mail::assertQueued(OrderConfirmation::class, 1);
    expect(EmailLog::where('dedupe_key', 'order.confirmation:'.$order->id)->count())->toBe(1);
});

it('sends nothing for an order whose checkout rolled back', function () {
    $order = notificationsOrder();

    Event::dispatch('checkout.order.save.after', $order);
    $order->delete();

    app(OrderConfirmationCoordinator::class)->flush();

    Mail::assertNotQueued(OrderConfirmation::class);
});

it('respects the admin switch for order confirmations', function () {
    bagistoMailSwitch('new_order', false);

    Event::dispatch('checkout.order.save.after', notificationsOrder());
    app(OrderConfirmationCoordinator::class)->flush();

    Mail::assertNotQueued(OrderConfirmation::class);
});

it('marks only the emailed invoice as sent', function () {
    $order = notificationsOrder();
    $emailed = Invoice::factory()->create(['order_id' => $order->id, 'email_sent' => 0]);
    $other = Invoice::factory()->create(['order_id' => $order->id, 'email_sent' => 0]);

    app(ShopInvoiceListener::class)->afterCreated($emailed);

    expect((int) $emailed->refresh()->email_sent)->toBe(1);
    expect((int) $other->refresh()->email_sent)->toBe(0);
});

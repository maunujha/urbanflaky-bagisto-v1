<?php

use Gabha\Notifications\Mail\OrderConfirmation;
use Gabha\Notifications\Mail\ReviewRequest;
use Gabha\Notifications\Mail\WinBack;
use Webkul\Customer\Models\Customer;
use Webkul\Sales\Models\Invoice;

require_once __DIR__.'/Helpers.php';

/* Rendering only: render() builds the HTML and never touches a transport. */

it('renders the review request with a link per product and its opt-out', function () {
    $html = (new ReviewRequest(notificationsOrder(), 'https://urbanflaky.in/email/unsubscribe?x=1'))->render();

    expect($html)
        ->toContain(trans('notifications::app.emails.review.title'))
        ->toContain('#review')
        ->toContain('https://urbanflaky.in/email/unsubscribe?x=1');
});

it('renders the win-back email with its unsubscribe link', function () {
    $mail = new WinBack(Customer::factory()->create(), 'https://urbanflaky.in/email/unsubscribe?x=2');

    expect($mail->render())->toContain(trans('notifications::app.emails.win-back.title'));
    expect($mail->headers()->text)->toHaveKey('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
});

/* ---- Order confirmation: order, invoice and payment kept apart ---- */

it('confirms a verified prepaid payment with its reference and attaches the invoice', function () {
    $order = notificationsOrder();
    Invoice::factory()->create(['order_id' => $order->id]);
    capturedPayment($order, 'pay_VERIFIED42');

    $mail = new OrderConfirmation($order->refresh());

    expect($mail->envelope()->subject)->toBe(trans('notifications::app.emails.order.paid-subject', ['order_id' => $order->increment_id]));
    expect($mail->render())
        ->toContain('pay_VERIFIED42')
        ->toContain(trans('shop::app.emails.orders.created.invoice-attached'));

    $attachments = $mail->attachments();

    expect($attachments)->toHaveCount(1);
    expect($attachments[0]->as)->toEndWith('.pdf');
});

it('never claims payment for a prepaid order without a verified capture', function () {
    $order = notificationsOrder();
    Invoice::factory()->create(['order_id' => $order->id]);

    $mail = new OrderConfirmation($order->refresh());

    expect($mail->paymentTransaction())->toBeNull();
    expect($mail->render())->not->toContain('Payment of');
    expect($mail->attachments())->toBeEmpty();
});

it('tells a cash-on-delivery customer what to pay, with no payment or invoice', function () {
    $order = notificationsOrder(['grand_total' => 799], 'cashondelivery');

    /* Even when Bagisto raised an invoice at checkout and a transaction row exists. */
    Invoice::factory()->create(['order_id' => $order->id]);
    capturedPayment($order);

    $mail = new OrderConfirmation($order->refresh());

    expect($mail->envelope()->subject)->toBe(trans('notifications::app.emails.order.cod-subject', ['order_id' => $order->increment_id]));
    expect($mail->paymentTransaction())->toBeNull();

    $html = $mail->render();

    expect($html)
        ->toContain('Cash on delivery: please keep')
        ->not->toContain('Payment of')
        ->not->toContain(trans('shop::app.emails.orders.created.invoice-attached'));
    expect($mail->attachments())->toBeEmpty();
});

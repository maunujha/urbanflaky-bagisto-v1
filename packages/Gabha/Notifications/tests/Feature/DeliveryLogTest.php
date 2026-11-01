<?php

use Gabha\Notifications\Mail\ShipmentUpdate;
use Gabha\Notifications\Models\EmailLog;
use Gabha\Notifications\Services\EmailDispatcher;
use Illuminate\Support\Facades\Mail;
use Webkul\Shop\Mail\Customer\RegistrationNotification;

require_once __DIR__.'/Helpers.php';

/*
 * These tests let the mailer run so the send/sent events fire, on the
 * in-memory "array" transport: messages are kept in memory, never sent.
 */
beforeEach(function () {
    config(['mail.default' => 'array']);
});

it('marks a dispatched email as sent once the mailer delivers it', function () {
    $order = notificationsOrder();

    app(EmailDispatcher::class)->send(new ShipmentUpdate($order, 'delivered'), 'shipment.delivered', 'test:'.$order->id, $order);

    $log = EmailLog::where('dedupe_key', 'test:'.$order->id)->first();

    expect($log->status)->toBe(EmailLog::STATUS_SENT)
        ->and($log->recipient)->toBe('buyer@example.com')
        ->and($log->sent_at)->not->toBeNull();
});

it('logs Bagisto\'s own emails too', function () {
    $customer = \Webkul\Customer\Models\Customer::factory()->create(['email' => 'welcome@example.com']);

    Mail::send(new RegistrationNotification($customer));

    expect(EmailLog::where('recipient', 'welcome@example.com')->where('status', 'sent')->value('type'))->toBe('shop.registration');
});

it('never mails a placeholder address from a phone-only sign-up', function () {
    $customer = \Webkul\Customer\Models\Customer::factory()->create(['email' => '9876543210@noreply.urbanflaky.in']);

    Mail::send(new RegistrationNotification($customer));

    expect(Mail::mailer('array')->getSymfonyTransport()->messages())->toHaveCount(0);
    expect(EmailLog::where('recipient', '9876543210@noreply.urbanflaky.in')->exists())->toBeFalse();

    $order = notificationsOrder(['customer_email' => '9876543210@noreply.urbanflaky.in']);

    expect(app(EmailDispatcher::class)->send(new ShipmentUpdate($order, 'delivered'), 'shipment.delivered'))->toBeFalse();
});

it('never emails twice when a job that already sent runs again', function () {
    $order = notificationsOrder();
    app(EmailDispatcher::class)->send(new ShipmentUpdate($order, 'delivered'), 'shipment.delivered', 'twice:'.$order->id, $order);

    $log = EmailLog::where('dedupe_key', 'twice:'.$order->id)->sole();
    expect($log->status)->toBe(EmailLog::STATUS_SENT);

    /* The same job, replayed by a retry. */
    $replay = new ShipmentUpdate($order, 'delivered');
    $replay->emailLogId = $log->id;
    Mail::send($replay);

    expect(Mail::mailer('array')->getSymfonyTransport()->messages())->toHaveCount(1);
});

it('lets a failed email be retried under the same key', function () {
    Mail::fake();

    $order = notificationsOrder();
    $dispatcher = app(EmailDispatcher::class);

    expect($dispatcher->send(new ShipmentUpdate($order, 'delivered'), 'shipment.delivered', 'retry:'.$order->id))->toBeTrue();
    expect($dispatcher->send(new ShipmentUpdate($order, 'delivered'), 'shipment.delivered', 'retry:'.$order->id))->toBeFalse();

    EmailLog::where('dedupe_key', 'retry:'.$order->id)->update(['status' => EmailLog::STATUS_FAILED]);

    expect($dispatcher->send(new ShipmentUpdate($order, 'delivered'), 'shipment.delivered', 'retry:'.$order->id))->toBeTrue();
    Mail::assertQueued(ShipmentUpdate::class, 2);
});

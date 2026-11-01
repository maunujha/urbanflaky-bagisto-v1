<?php

use Gabha\Notifications\Mail\OrderConfirmation;
use Gabha\Notifications\Mail\ShipmentUpdate;
use Gabha\Notifications\Mail\WinBack;
use Gabha\Notifications\Models\EmailLog;
use Gabha\Notifications\Models\EmailSuppression;
use Gabha\Notifications\Services\EmailDispatcher;
use Gabha\Notifications\Services\MarketingGuard;
use Gabha\Notifications\Services\OrderConfirmationCoordinator;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Webkul\Core\Models\SubscribersList;
use Webkul\Customer\Models\Customer;
use Webkul\Sales\Models\Refund;
use Webkul\Shop\Mail\Order\CanceledNotification;
use Webkul\Shop\Mail\Order\RefundedNotification;
use Webkul\User\Models\Admin;

/*
 * Delivery guarantees: retries never double-send, failures never block an
 * order, opted-out addresses are withheld and logged, and admin can re-queue
 * a failed email exactly once.
 */

require_once __DIR__.'/Helpers.php';

beforeEach(function () {
    Mail::fake();
});

/** A queue job that failed while sending $mailable. */
function failedMailJob(object $mailable, Throwable $exception, string $uuid): JobFailed
{
    $job = Mockery::mock(Job::class);
    $job->shouldReceive('uuid')->andReturn($uuid);
    $job->shouldReceive('payload')->andReturn([
        'uuid'        => $uuid,
        'displayName' => $mailable::class,
        'data'        => ['commandName' => SendQueuedMailable::class, 'command' => serialize(new SendQueuedMailable($mailable))],
    ]);

    return new JobFailed('redis', $job, $exception);
}

/** A failed email whose job is kept in failed_jobs, as the worker leaves it. */
function failedEmail(array $attributes = []): EmailLog
{
    $uuid = (string) Str::uuid();

    app('queue.failer')->log('redis', 'default', json_encode(['uuid' => $uuid, 'displayName' => ShipmentUpdate::class]), new RuntimeException('SMTP timeout'));

    return EmailLog::create(array_merge([
        'type' => 'shipment.delivered', 'mailable' => ShipmentUpdate::class, 'recipient' => 'buyer@example.com',
        'status' => EmailLog::STATUS_FAILED, 'error' => 'SMTP timeout', 'job_uuid' => $uuid, 'dedupe_key' => 'shipment:1:delivered',
    ], $attributes));
}

/* ---- Retry policy ---- */

it('retries a failing send with growing back-off', function () {
    $job = new SendQueuedMailable(new ShipmentUpdate(notificationsOrder(), 'delivered'));

    expect($job->tries)->toBe(4)
        ->and($job->backoff())->toBe([60, 300, 900]);

    expect((new SendQueuedMailable(new OrderConfirmation(notificationsOrder())))->tries)->toBe(4);
});

/* ---- Failed jobs ---- */

it('marks the email failed and keeps its job for a retry', function () {
    $mail = new ShipmentUpdate(notificationsOrder(), 'delivered');
    $mail->emailLogId = EmailLog::create(['type' => 'shipment.delivered', 'mailable' => 'x', 'recipient' => 'buyer@example.com', 'status' => 'queued'])->id;

    event(failedMailJob($mail, new RuntimeException('451 Too many messages'), 'job-123'));

    expect(EmailLog::find($mail->emailLogId))
        ->status->toBe(EmailLog::STATUS_FAILED)
        ->error->toBe('451 Too many messages')
        ->job_uuid->toBe('job-123');
});

it('does not offer a retry when the customer, order or product was deleted', function () {
    $mail = new ShipmentUpdate(notificationsOrder(), 'delivered');
    $mail->emailLogId = EmailLog::create(['type' => 'shipment.delivered', 'mailable' => 'x', 'recipient' => 'buyer@example.com', 'status' => 'queued'])->id;

    event(failedMailJob($mail, (new ModelNotFoundException)->setModel(Customer::class), 'job-456'));

    $log = EmailLog::find($mail->emailLogId);

    expect($log->status)->toBe(EmailLog::STATUS_FAILED)
        ->and($log->error)->toContain('no longer exists')
        ->and($log->canRetry())->toBeFalse();
});

/* ---- Never block an order ---- */

it('never fails checkout when the mail queue is down', function () {
    bagistoMailSwitch('new_order');
    Mail::shouldReceive('queue')->andThrow(new RuntimeException('Connection refused [tcp://127.0.0.1:6379]'));

    $order = notificationsOrder();

    Event::dispatch('checkout.order.save.after', $order);
    app(OrderConfirmationCoordinator::class)->flush();

    expect(EmailLog::where('related_id', $order->id)->value('status'))->toBe(EmailLog::STATUS_FAILED);
});

it('skips an order without a usable email address, without an error', function (?string $email) {
    bagistoMailSwitch('new_order');

    Event::dispatch('checkout.order.save.after', notificationsOrder(['customer_email' => $email]));
    app(OrderConfirmationCoordinator::class)->flush();

    Mail::assertNotQueued(OrderConfirmation::class);
})->with([
    'none'        => [null],
    'placeholder' => ['9876543210@noreply.urbanflaky.in'],
]);

/* ---- Cancellation and refunds (Bagisto's own emails) ---- */

it('emails the customer once when an order is cancelled', function () {
    bagistoMailSwitch('cancel_order');

    Event::dispatch('sales.order.cancel.after', notificationsOrder(['status' => 'canceled']));

    Mail::assertQueued(CanceledNotification::class, 1);
});

it('emails each refund, partial then full, and marks only that refund as sent', function () {
    bagistoMailSwitch('new_refund');
    /* COD: a Razorpay order would also run the gateway refund, not under test here. */
    $order = notificationsOrder(['grand_total' => 1598], 'cashondelivery');

    $refund = fn (float $amount) => Refund::factory()->create([
        'order_id' => $order->id, 'state' => 'refunded', 'grand_total' => $amount, 'base_grand_total' => $amount, 'order_currency_code' => 'INR',
    ]);

    $partial = $refund(799);
    $untouched = $refund(0);
    Event::dispatch('sales.refund.save.after', $partial);

    $rest = $refund(799);
    Event::dispatch('sales.refund.save.after', $rest);

    Mail::assertQueued(RefundedNotification::class, 2);
    Mail::assertQueued(RefundedNotification::class, fn ($mail) => $mail->refund->id === $partial->id);
    Mail::assertQueued(RefundedNotification::class, fn ($mail) => $mail->refund->id === $rest->id);

    expect(Refund::whereKey([$partial->id, $rest->id])->pluck('email_sent')->map(fn ($sent) => (int) $sent)->all())->toBe([1, 1])
        ->and((int) $untouched->refresh()->email_sent)->toBe(0);
});

/* ---- Suppressed ---- */

it('withholds marketing from an opted-out address, logs it once and leaves the cap alone', function () {
    $customer = Customer::factory()->create(['email' => 'gone@example.com']);
    EmailSuppression::suppress('gone@example.com', EmailSuppression::SCOPE_MARKETING);

    $before = app(MarketingGuard::class)->remainingToday();

    foreach ([1, 2] as $run) {
        expect(app(EmailDispatcher::class)->send(new WinBack($customer, 'https://urbanflaky.in/u'), 'marketing.win_back', 'winback:'.$customer->id, $customer, EmailLog::CATEGORY_MARKETING))->toBeFalse();
    }

    Mail::assertNothingQueued();
    expect(EmailLog::where('recipient', 'gone@example.com')->pluck('status')->all())->toBe([EmailLog::STATUS_SUPPRESSED])
        ->and(app(MarketingGuard::class)->remainingToday())->toBe($before);
});

it('never suppresses an order email for a marketing opt-out', function () {
    EmailSuppression::suppress('buyer@example.com', EmailSuppression::SCOPE_MARKETING);
    $order = notificationsOrder();

    expect(app(EmailDispatcher::class)->send(new ShipmentUpdate($order, 'delivered'), 'shipment.delivered', 'tx:'.$order->id, $order))->toBeTrue();
});

/* ---- Admin: retry, statuses, consent ---- */

describe('admin', function () {
    beforeEach(function () {
        $this->actingAs(Admin::factory()->create(), 'admin');
    });

    it('re-queues a failed email once', function () {
        $log = failedEmail();
        Artisan::shouldReceive('call')->once()->with('queue:retry', ['id' => [$log->job_uuid]]);

        $this->post(route('admin.notifications.logs.retry', $log->id))->assertRedirect()->assertSessionHas('success');

        expect($log->refresh())->status->toBe(EmailLog::STATUS_QUEUED)->job_uuid->toBeNull();

        /* A second click finds nothing to retry. */
        $this->post(route('admin.notifications.logs.retry', $log->id))->assertSessionHas('error');
    });

    it('restores the dedupe key a failed attempt parked', function () {
        $log = failedEmail();
        app(EmailDispatcher::class)->alreadySent('shipment:1:delivered');
        expect($log->refresh()->dedupe_key)->toBe('shipment:1:delivered#failed'.$log->id);

        Artisan::shouldReceive('call')->once();
        $this->post(route('admin.notifications.logs.retry', $log->id));

        expect($log->refresh()->dedupe_key)->toBe('shipment:1:delivered');
    });

    it('refuses to retry an email that has gone out another way since', function () {
        $log = failedEmail();
        app(EmailDispatcher::class)->alreadySent('shipment:1:delivered');
        EmailLog::create(['type' => 'shipment.delivered', 'mailable' => 'x', 'recipient' => 'buyer@example.com', 'status' => 'sent', 'dedupe_key' => 'shipment:1:delivered']);

        Artisan::shouldReceive('call')->never();

        $this->post(route('admin.notifications.logs.retry', $log->id))->assertSessionHas('error');
        expect($log->refresh()->status)->toBe(EmailLog::STATUS_FAILED);
    });

    it('refuses to retry when the failed job is no longer kept', function () {
        $log = failedEmail(['job_uuid' => (string) Str::uuid()]);
        Artisan::shouldReceive('call')->never();

        $this->post(route('admin.notifications.logs.retry', $log->id))->assertSessionHas('error');
    });

    it('shows statuses, the retry button and filters by Suppressed', function () {
        failedEmail();
        EmailLog::create(['type' => 'marketing.win_back', 'mailable' => 'x', 'recipient' => 'quiet@example.com', 'status' => 'suppressed', 'error' => 'Unsubscribed from marketing email']);

        $this->get(route('admin.notifications.logs.index'))
            ->assertOk()
            ->assertSee(trans('notifications::app.admin.statuses.suppressed'))
            ->assertSee(trans('notifications::app.admin.retry.button'));

        $this->get(route('admin.notifications.logs.index', ['status' => 'suppressed']))
            ->assertSee('quiet@example.com')
            ->assertDontSee('buyer@example.com');
    });

    it('shows marketing consent for a searched address', function () {
        SubscribersList::create(['email' => 'reader@example.com', 'is_subscribed' => 1, 'token' => uniqid(), 'channel_id' => core()->getDefaultChannel()->id]);

        $this->get(route('admin.notifications.logs.index', ['q' => 'reader@example.com']))
            ->assertOk()
            ->assertSee(trans('notifications::app.admin.consent.subscribed'))
            ->assertSee('granted');

        $this->get(route('admin.notifications.logs.index', ['q' => 'reader']))
            ->assertDontSee(trans('notifications::app.admin.consent.title'));
    });
});

<?php

use Gabha\Notifications\Models\EmailLog;
use Illuminate\Support\Facades\Mail;
use Webkul\User\Models\Admin;

require_once __DIR__.'/Helpers.php';

beforeEach(function () {
    Mail::fake();

    $this->actingAs(Admin::factory()->create(), 'admin');
});

it('lists logged emails with their status', function () {
    EmailLog::create([
        'type' => 'shipment.delivered', 'mailable' => 'x', 'recipient' => 'listed@example.com',
        'subject' => 'Delivered: order #1', 'status' => EmailLog::STATUS_FAILED, 'error' => 'SMTP timeout',
    ]);

    $this->get(route('admin.notifications.logs.index'))
        ->assertOk()
        ->assertSee('listed@example.com')
        ->assertSee('SMTP timeout');

    $this->get(route('admin.notifications.logs.index', ['status' => 'sent']))
        ->assertOk()
        ->assertDontSee('listed@example.com');
});

it('lists customer feedback', function () {
    $order = notificationsOrder();
    Gabha\Notifications\Models\OrderFeedback::create(['order_id' => $order->id, 'email' => 'buyer@example.com', 'rating' => 5, 'comment' => 'Perfect fit']);

    $this->get(route('admin.notifications.feedback.index'))->assertOk()->assertSee('Perfect fit');
});

it('is only for signed-in admins', function () {
    auth()->guard('admin')->logout();

    $this->get(route('admin.notifications.logs.index'))->assertRedirect();
    $this->get(route('admin.notifications.templates.index'))->assertRedirect();
});

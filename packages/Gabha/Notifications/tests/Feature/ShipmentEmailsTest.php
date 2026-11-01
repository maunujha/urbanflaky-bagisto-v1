<?php

use Gabha\Notifications\Mail\ShipmentUpdate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

require_once __DIR__.'/Helpers.php';

beforeEach(function () {
    Mail::fake();
});

function stageChanged($order, ?string $from, string $to): void
{
    Event::dispatch('shiprocket.shipment.stage_changed', [$order, $from, $to]);
}

it('emails the customer when the courier goes out for delivery', function (string $to, string $stage) {
    $order = notificationsOrder();

    stageChanged($order, 'in_transit', $to);

    Mail::assertQueued(ShipmentUpdate::class, fn ($mail) => $mail->stage === $stage && $mail->order->id === $order->id);
})->with([
    ['out_for_delivery', 'out_for_delivery'],
    ['delivered', 'delivered'],
    ['undelivered', 'delivery_failed'],
    ['rto_initiated', 'rto'],
]);

it('tells the customer the order is being packed when the courier label is created', function () {
    $order = notificationsOrder(['created_at' => now()->subHours(3)]);

    stageChanged($order, 'new', 'awb_assigned');

    Mail::assertQueued(ShipmentUpdate::class, fn ($mail) => $mail->stage === 'processing');
});

it('skips the packing email when the label comes right after the order', function () {
    stageChanged(notificationsOrder(['created_at' => now()->subMinutes(5)]), 'new', 'awb_assigned');

    Mail::assertNothingQueued();
});

it('sends each stage email once, even when a retried webhook repeats it', function () {
    $order = notificationsOrder();

    stageChanged($order, 'out_for_delivery', 'delivered');
    stageChanged($order, 'out_for_delivery', 'delivered');

    Mail::assertQueued(ShipmentUpdate::class, 1);
});

it('stays quiet for stages Bagisto or nobody needs to hear about', function (string $to) {
    stageChanged(notificationsOrder(), null, $to);

    Mail::assertNothingQueued();
})->with(['pickup_scheduled', 'picked_up', 'in_transit', 'rto_delivered', 'canceled', 'lost']);

it('respects the admin switch for each stage', function () {
    notificationsConfig(['emails.automation.shipping.delivered' => '0']);

    stageChanged(notificationsOrder(), 'out_for_delivery', 'delivered');

    Mail::assertNothingQueued();
});

it('does not email about a cancelled order', function () {
    stageChanged(notificationsOrder(['status' => 'canceled']), 'in_transit', 'rto_initiated');

    Mail::assertNothingQueued();
});

it('renders every stage email with its copy', function (string $stage) {
    $html = (new ShipmentUpdate(notificationsOrder(), $stage))->render();

    expect($html)->toContain(trans('notifications::app.emails.shipment.'.$stage.'.title'));
})->with(['processing', 'out_for_delivery', 'delivered', 'delivery_failed', 'rto']);

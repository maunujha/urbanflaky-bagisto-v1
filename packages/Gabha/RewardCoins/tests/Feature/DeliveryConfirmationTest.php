<?php

use Gabha\RewardCoins\Enums\TransactionStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    config(['shiprocket.webhook_token' => 'test-webhook-token']);
});

function shiprocketRow(int $orderId, string $status, ?Carbon $updatedAt = null): void
{
    DB::table('shiprocket_orders')->insert([
        'order_id'   => $orderId,
        'status'     => $status,
        'created_at' => $updatedAt ?? now(),
        'updated_at' => $updatedAt ?? now(),
    ]);
}

function postTrackingWebhook($test, $order, string $status)
{
    return $test->withHeader('x-api-key', 'test-webhook-token')->postJson('/webhooks/tracking', [
        'channel_order_id' => (string) $order->increment_id,
        'current_status'   => $status,
        'awb'              => '1234567890',
    ]);
}

it('opens the window when a manually fulfilled order is completed', function () {
    Carbon::setTestNow('2026-10-10 10:00:00');

    $customer = $this->customer();
    $order    = $this->order($customer);
    $lot      = $this->earn($order, 50);

    $order->update(['status' => 'completed']);
    Event::dispatch('sales.order.update-status.after', $order);

    expect($lot->refresh()->available_at->toDateTimeString())->toBe('2026-10-17 10:00:00');
    expect($lot->status)->toBe(TransactionStatus::Pending);
});

it('ignores the transient completed status of a Shiprocket order that is only shipped', function () {
    $customer = $this->customer();
    $order    = $this->order($customer);
    $lot      = $this->earn($order, 50);
    shiprocketRow($order->id, 'shipped');

    // Bagisto marks a fully shipped order completed before MarkOrderShipped
    // turns it into `shipped`.
    $order->update(['status' => 'completed']);
    Event::dispatch('sales.order.update-status.after', $order);

    expect($lot->refresh()->available_at)->toBeNull();
});

it('opens the window from the Shiprocket tracking webhook, once', function () {
    Carbon::setTestNow('2026-10-10 10:00:00');

    $customer = $this->customer();
    $order    = $this->order($customer, ['status' => 'shipped']);
    $lot      = $this->earn($order, 50);
    shiprocketRow($order->id, 'shipped');

    postTrackingWebhook($this, $order, 'Delivered')->assertOk();

    expect($lot->refresh()->available_at->toDateTimeString())->toBe('2026-10-17 10:00:00');

    // Shiprocket re-sends the same webhook two days later.
    Carbon::setTestNow('2026-10-12 10:00:00');
    postTrackingWebhook($this, $order, 'DELIVERED')->assertOk();

    expect($lot->refresh()->available_at->toDateTimeString())->toBe('2026-10-17 10:00:00');
});

it('does not treat RTO Delivered as delivery', function () {
    $customer = $this->customer();
    $order    = $this->order($customer, ['status' => 'shipped']);
    $lot      = $this->earn($order, 50);
    shiprocketRow($order->id, 'shipped');

    postTrackingWebhook($this, $order, 'RTO Delivered')->assertOk();

    expect($lot->refresh()->available_at)->toBeNull();
});

it('has retired the legacy Shiprocket API webhook', function () {
    $customer = $this->customer();
    $order    = $this->order($customer, ['status' => 'shipped']);
    $lot      = $this->earn($order, 50);

    $this->withHeader('x-api-key', 'test-webhook-token')->postJson('/api/webhooks/shiprocket', [
        'channel_order_id' => (string) $order->increment_id,
        'current_status'   => 'Delivered',
    ])->assertStatus(405); // only the storefront's GET catch-all matches now

    expect($order->refresh()->status)->toBe('shipped');
    expect($lot->refresh()->available_at)->toBeNull();
});

it('rejects a query-string token on the tracking webhook', function () {
    $customer = $this->customer();
    $order    = $this->order($customer, ['status' => 'shipped']);
    $lot      = $this->earn($order, 50);
    shiprocketRow($order->id, 'shipped');

    $this->postJson('/webhooks/tracking?token=test-webhook-token', [
        'channel_order_id' => (string) $order->increment_id,
        'current_status'   => 'Delivered',
    ])->assertUnauthorized();

    expect($lot->refresh()->available_at)->toBeNull();
});

it('rejects the tracking webhook when no token is configured', function () {
    config(['shiprocket.webhook_token' => null]);

    $customer = $this->customer();
    $order    = $this->order($customer, ['status' => 'shipped']);
    $lot      = $this->earn($order, 50);
    shiprocketRow($order->id, 'shipped');

    $this->withHeader('x-api-key', '')->postJson('/webhooks/tracking', [
        'channel_order_id' => (string) $order->increment_id,
        'current_status'   => 'Delivered',
    ])->assertUnauthorized();

    expect($lot->refresh()->available_at)->toBeNull();
});

it('never logs customer data from the tracking webhook payload', function () {
    $customer = $this->customer();
    $order    = $this->order($customer, ['status' => 'shipped']);
    shiprocketRow($order->id, 'shipped');

    $logged = [];
    \Illuminate\Support\Facades\Log::listen(function ($message) use (&$logged) {
        $logged[] = json_encode([$message->message, $message->context]);
    });

    $this->withHeader('x-api-key', 'test-webhook-token')->postJson('/webhooks/tracking', [
        'channel_order_id' => (string) $order->increment_id,
        'current_status'   => 'In Transit',
        'customer_name'    => 'Secret Person',
        'customer_phone'   => '9999988888',
        'customer_address' => '221B Private Lane',
    ])->assertOk();

    $all = implode("\n", $logged);

    expect($all)->toContain((string) $order->increment_id);
    expect($all)->not->toContain('Secret Person');
    expect($all)->not->toContain('9999988888');
    expect($all)->not->toContain('221B Private Lane');
});

it('confirms coins once however often the daily job runs', function () {
    $customer = $this->customer();
    $order    = $this->order($customer);
    $lot      = $this->earn($order, 50);

    $order->update(['status' => 'completed']);
    Event::dispatch('sales.order.update-status.after', $order);

    $this->travel(6)->days();
    $this->artisan('reward-coins:confirm-available')->assertSuccessful();
    expect($lot->refresh()->status)->toBe(TransactionStatus::Pending);

    $this->travel(2)->days();
    $this->artisan('reward-coins:confirm-available')->assertSuccessful();
    $this->artisan('reward-coins:confirm-available')->assertSuccessful();

    expect($lot->refresh()->status)->toBe(TransactionStatus::Confirmed);

    $wallet = $this->walletRow($customer);
    expect($wallet->balance)->toBe(50);
    expect($wallet->pending_balance)->toBe(0);

    $this->assertConsistent($customer);
});

it('reconciles a delivered order whose window was never opened', function () {
    $customer = $this->customer();
    $order    = $this->order($customer, ['status' => 'shipped']);
    $lot      = $this->earn($order, 50);

    // Delivered 10 days ago, but the webhook never reached the coin listener.
    shiprocketRow($order->id, 'delivered', now()->subDays(10));

    $this->artisan('reward-coins:confirm-available')->assertSuccessful();

    expect($lot->refresh()->status)->toBe(TransactionStatus::Confirmed);
    expect($this->walletRow($customer)->balance)->toBe(50);

    // Running again changes nothing.
    $this->artisan('reward-coins:confirm-available')->assertSuccessful();
    expect($this->walletRow($customer)->balance)->toBe(50);

    $this->assertConsistent($customer);
});

it('reconciliation never releases coins of undelivered or reversed orders', function () {
    $customer = $this->customer();

    $shipped = $this->order($customer, ['status' => 'shipped']);
    $a       = $this->earn($shipped, 10);
    shiprocketRow($shipped->id, 'in transit');

    $closed = $this->order($customer, ['status' => 'closed']);
    $b      = $this->earn($closed, 20);

    $pendingStatus = $this->order($customer, ['status' => 'processing']);
    $c             = $this->earn($pendingStatus, 30);

    $this->travel(30)->days();
    $this->artisan('reward-coins:confirm-available')->assertSuccessful();

    foreach ([$a, $b, $c] as $lot) {
        expect($lot->refresh()->status)->toBe(TransactionStatus::Pending);
        expect($lot->available_at)->toBeNull();
    }

    expect($this->walletRow($customer)->balance)->toBe(0);
});

it('keeps the delivery window when a stale webhook arrives after delivery', function () {
    Carbon::setTestNow('2026-10-10 10:00:00');

    $customer = $this->customer();
    $order    = $this->order($customer, ['status' => 'shipped']);
    $lot      = $this->earn($order, 50);
    shiprocketRow($order->id, 'shipped');

    postTrackingWebhook($this, $order, 'Delivered')->assertOk();

    // A delayed "In Transit" lands afterwards.
    Carbon::setTestNow('2026-10-11 10:00:00');
    postTrackingWebhook($this, $order, 'In Transit')->assertOk();

    expect($lot->refresh()->available_at->toDateTimeString())->toBe('2026-10-17 10:00:00');

    // Not spendable before the window ends, spendable after.
    Carbon::setTestNow('2026-10-16 10:00:00');
    $this->artisan('reward-coins:confirm-available')->assertSuccessful();
    expect($lot->refresh()->status)->toBe(TransactionStatus::Pending);

    Carbon::setTestNow('2026-10-17 10:00:01');
    $this->artisan('reward-coins:confirm-available')->assertSuccessful();
    expect($lot->refresh()->status)->toBe(TransactionStatus::Confirmed);
});

it('reports windows opened before delivery without changing them', function () {
    Carbon::setTestNow('2026-10-10 10:00:00');

    $customer = $this->customer();

    // Opened at shipment (old behaviour), never delivered.
    $undelivered = $this->order($customer, ['status' => 'shipped']);
    $a           = $this->earn($undelivered, 10);
    shiprocketRow($undelivered->id, 'in transit');
    $a->update(['available_at' => now()->addDays(2)]);

    // Opened at shipment, delivered three days later.
    $early = $this->order($customer, ['status' => 'shipped']);
    $b     = $this->earn($early, 20);
    shiprocketRow($early->id, 'delivered', now()->subDays(2));
    $b->update(['available_at' => now()->addDays(2)]);   // opened 5 days ago

    // Valid: opened at delivery.
    $valid = $this->order($customer, ['status' => 'shipped']);
    $c     = $this->earn($valid, 30);
    shiprocketRow($valid->id, 'delivered', now()->subDay());
    $c->update(['available_at' => now()->addDays(6)]);

    $rows = collect(app(\Gabha\RewardCoins\Services\CoinDeliveryService::class)->auditWindows())->keyBy('lot');

    expect($rows->keys()->sort()->values()->all())->toBe([$a->id, $b->id]);
    expect($rows[$a->id]['proposed_action'])->toBe('clear available_at; reopens on delivery');
    expect($rows[$b->id]['proposed_action'])->toBe('move available_at to delivery + window');
    expect($rows[$b->id]['proposed_available'])->toBe('2026-10-15 10:00:00');

    // Read-only.
    expect($a->refresh()->available_at->toDateTimeString())->toBe('2026-10-12 10:00:00');
    expect($b->refresh()->available_at->toDateTimeString())->toBe('2026-10-12 10:00:00');

    $this->artisan('reward-coins:audit-windows')->assertSuccessful();
});

it('skips confirming coins whose order was reversed after the window opened', function () {
    $customer = $this->customer();
    $order    = $this->order($customer, ['status' => 'completed']);
    $lot      = $this->earn($order, 40);

    $lot->update(['available_at' => now()->subDay()]);
    $order->update(['status' => 'closed']);

    $this->artisan('reward-coins:confirm-available')->assertSuccessful();

    expect($lot->refresh()->status)->toBe(TransactionStatus::Pending);
    expect($this->walletRow($customer)->balance)->toBe(0);
});

<?php

use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Jobs\ReverseCoinsForOrder;
use Gabha\RewardCoins\Listeners\RevokeCoinsOnRefund;
use Gabha\RewardCoins\Services\CoinOrderReversalService;
use Illuminate\Support\Facades\Event;

/**
 * Fire the refund listener the way Bagisto does after a refund is saved.
 */
function refundEvent(int $orderId, int $refundId = 1): void
{
    app(RevokeCoinsOnRefund::class)->handle((object) ['id' => $refundId, 'order_id' => $orderId]);
}

/**
 * A refund row as written before coin shares existed (coins_refunded NULL).
 */
function legacyRefund(int $orderId, float $grandTotal): void
{
    \Illuminate\Support\Facades\DB::table('refunds')->insert([
        'order_id'         => $orderId,
        'state'            => 'refunded',
        'grand_total'      => $grandTotal,
        'base_grand_total' => $grandTotal,
        'coins_refunded'   => null,
        'created_at'       => now(),
        'updated_at'       => now(),
    ]);
}

it('voids pending coins when an order is cancelled', function () {
    $customer = $this->customer();
    $order    = $this->order($customer);
    $lot      = $this->earn($order, 79);

    $order->update(['status' => 'canceled']);
    Event::dispatch('sales.order.update-status.after', $order);

    expect($lot->refresh()->status)->toBe(TransactionStatus::Cancelled);
    expect($lot->remaining)->toBe(0);

    $wallet = $this->walletRow($customer);
    expect($wallet->pending_balance)->toBe(0);
    expect($wallet->lifetime_earned)->toBe(0);
    expect($wallet->balance)->toBe(0);

    $revoked = $this->rows($customer, TransactionType::Revoked);
    expect($revoked)->toHaveCount(1);
    expect($revoked[0]->amount)->toBe(79);
    expect($revoked[0]->operation_key)->toBe("reversal:order:{$order->id}:void-pending");

    $this->assertConsistent($customer);
});

it('claws back confirmed coins when an order is cancelled', function () {
    $customer = $this->customer();
    $order    = $this->order($customer);
    $this->earnConfirmed($order, 79);

    $order->update(['status' => 'canceled']);
    Event::dispatch('sales.order.update-status.after', $order);

    expect($this->walletRow($customer)->balance)->toBe(0);
    expect($this->rows($customer, TransactionType::Revoked)->sum('amount'))->toBe(79);

    $this->assertConsistent($customer);
});

it('fully refunds an order: earned revoked, redeemed restored', function () {
    $customer = $this->customer();
    $this->earnConfirmed($this->order($customer), 300);

    $order = $this->order($customer, ['base_grand_total' => 850]);
    $this->redeem($order, 150);
    $this->earnConfirmed($order, 100);

    expect($this->walletRow($customer)->balance)->toBe(250);

    $order->update(['status' => 'closed', 'base_sub_total_refunded' => 1000, 'base_grand_total_refunded' => 850]);
    refundEvent($order->id);

    $wallet = $this->walletRow($customer);
    expect($wallet->balance)->toBe(250 - 100 + 150);
    expect($wallet->lifetime_redeemed)->toBe(0);
    expect($this->rows($customer, TransactionType::Revoked)->sum('amount'))->toBe(100);
    expect($this->rows($customer, TransactionType::Refunded)->sum('amount'))->toBe(150);
    expect($this->rows($customer, TransactionType::Redeemed)[0]->status)->toBe(TransactionStatus::Reversed);

    $this->assertConsistent($customer);
});

it('partially refunds by merchandise share, rounding up once', function () {
    $customer = $this->customer();
    $order    = $this->order($customer);
    $lot      = $this->earnConfirmed($order, 79);

    $order->update(['base_sub_total_refunded' => 500, 'base_grand_total_refunded' => 500]);
    refundEvent($order->id);

    expect($this->rows($customer, TransactionType::Revoked)->sum('amount'))->toBe(40);
    expect($lot->refresh()->remaining)->toBe(39);
    expect($this->walletRow($customer)->balance)->toBe(39);

    $this->assertConsistent($customer);
});

it('settles multiple partial refunds without rounding drift', function (array $steps) {
    $customer = $this->customer();
    $order    = $this->order($customer, ['base_sub_total' => 900, 'base_grand_total' => 900]);
    $this->earnConfirmed($order, 79);

    foreach ($steps as $i => $refunded) {
        $order->update(['base_sub_total_refunded' => $refunded, 'base_grand_total_refunded' => $refunded]);
        refundEvent($order->id, $i + 1);
    }

    $last = end($steps);

    expect($this->rows($customer, TransactionType::Revoked)->sum('amount'))
        ->toBe((int) ceil(round(79 * $last / 900, 6)));

    $this->assertConsistent($customer);
})->with([
    'thirds'           => [[300, 600, 900]],
    'big then small'   => [[600, 900]],
    'small then big'   => [[100, 900]],
    'odd slices'       => [[123, 457, 701]],
]);

it('ignores duplicate events for a refund recorded before coin shares (legacy ratio)', function () {
    $customer = $this->customer();
    $order    = $this->order($customer, ['base_grand_total' => 900]);
    $this->earnConfirmed($this->order($customer), 200);
    $this->redeem($order, 100);
    $this->earnConfirmed($order, 90);

    $order->update(['base_sub_total_refunded' => 500, 'base_grand_total_refunded' => 450]);
    legacyRefund($order->id, 450);

    refundEvent($order->id, 7);
    refundEvent($order->id, 7);
    app(CoinOrderReversalService::class)->sync($order->id, 'replay');

    expect($this->rows($customer, TransactionType::Revoked)->sum('amount'))->toBe(45);
    expect($this->rows($customer, TransactionType::Refunded)->sum('amount'))->toBe(50);

    $this->assertConsistent($customer);
});

it('does not reverse twice when an order is cancelled and then refunded', function () {
    $customer = $this->customer();
    $this->earnConfirmed($this->order($customer), 200);
    $order = $this->order($customer, ['base_grand_total' => 900]);
    $this->redeem($order, 100);
    $this->earn($order, 90);

    $order->update(['status' => 'canceled']);
    Event::dispatch('sales.order.update-status.after', $order);

    $order->update(['base_sub_total_refunded' => 1000, 'base_grand_total_refunded' => 900]);
    refundEvent($order->id);

    expect($this->rows($customer, TransactionType::Refunded)->sum('amount'))->toBe(100);
    expect($this->rows($customer, TransactionType::Revoked)->sum('amount'))->toBe(90);
    expect($this->walletRow($customer)->balance)->toBe(200);
    expect($this->walletRow($customer)->pending_balance)->toBe(0);

    $this->assertConsistent($customer);
});

it('restores redeemed coins once when the refund event and the closed-status job both run', function (bool $refundFirst) {
    $customer = $this->customer();
    $this->earnConfirmed($this->order($customer), 200);
    $order = $this->order($customer, ['base_grand_total' => 850]);
    $this->redeem($order, 150);

    $order->update(['status' => 'closed', 'base_sub_total_refunded' => 1000, 'base_grand_total_refunded' => 850]);

    if ($refundFirst) {
        refundEvent($order->id);
        (new ReverseCoinsForOrder($order->id))->handle(app(CoinOrderReversalService::class));
    } else {
        (new ReverseCoinsForOrder($order->id))->handle(app(CoinOrderReversalService::class));
        refundEvent($order->id);
    }

    expect($this->rows($customer, TransactionType::Refunded)->sum('amount'))->toBe(150);
    expect($this->walletRow($customer)->balance)->toBe(200);

    $this->assertConsistent($customer);
})->with(['refund first' => true, 'status job first' => false]);

it('records an unrecoverable claw-back once and never chases it', function () {
    $customer = $this->customer();
    $order    = $this->order($customer);
    $this->earnConfirmed($order, 79);

    // The customer spends every coin before the refund.
    $this->redeem($this->order($customer), 79);

    $order->update(['status' => 'closed', 'base_sub_total_refunded' => 1000, 'base_grand_total_refunded' => 1000]);
    refundEvent($order->id);

    $revoked = $this->rows($customer, TransactionType::Revoked);
    expect($revoked)->toHaveCount(1);
    expect($revoked[0]->amount)->toBe(0);
    expect($revoked[0]->meta['shortfall'])->toBe(79);
    expect($this->walletRow($customer)->balance)->toBe(0);

    // New coins later are not taken for the old shortfall.
    $this->earnConfirmed($this->order($customer), 30);
    refundEvent($order->id, 2);

    expect($this->walletRow($customer)->balance)->toBe(30);
    expect($this->rows($customer, TransactionType::Revoked))->toHaveCount(1);

    $this->assertConsistent($customer);
});

it('claws back from the rest of the balance when part of the earned lot was spent', function () {
    $customer = $this->customer();
    $order    = $this->order($customer);
    $lot      = $this->earnConfirmed($order, 50);

    $this->travel(1)->days();
    $other = $this->earnConfirmed($this->order($customer), 50);

    // Soonest-expiring lot ($lot) is spent first.
    $this->redeem($this->order($customer), 40);
    expect($lot->refresh()->remaining)->toBe(10);

    $order->update(['status' => 'closed', 'base_sub_total_refunded' => 1000, 'base_grand_total_refunded' => 1000]);
    refundEvent($order->id);

    expect($lot->refresh()->remaining)->toBe(0);
    expect($other->refresh()->remaining)->toBe(10);
    expect($this->walletRow($customer)->balance)->toBe(10);

    $this->assertConsistent($customer);
});

it('caps legacy-ratio restores at the redemption and keeps the source expiry', function () {
    $customer = $this->customer();
    $source   = $this->earnConfirmed($this->order($customer), 200);
    $order    = $this->order($customer, ['base_grand_total' => 850]);
    $this->redeem($order, 150);

    // Pre-fix refunds ignored the coin discount, so their totals can exceed
    // the paid total: half, then more than all of it.
    $order->update(['base_grand_total_refunded' => 425]);
    legacyRefund($order->id, 425);
    refundEvent($order->id, 1);

    $restored = $this->rows($customer, TransactionType::Refunded);
    expect($restored->sum('amount'))->toBe(75);
    expect($restored[0]->expires_at->equalTo($source->expires_at))->toBeTrue();

    $order->update(['base_grand_total_refunded' => 1000]);
    refundEvent($order->id, 2);

    expect($this->rows($customer, TransactionType::Refunded)->sum('amount'))->toBe(150);
    expect($this->walletRow($customer)->balance)->toBe(200);

    $this->assertConsistent($customer);
});

it('settles an order cancelled before its queued award job ran', function () {
    $customer = $this->customer();
    $order    = $this->order($customer, ['status' => 'canceled']);

    // The award lands after the cancellation reversal already ran.
    app(CoinOrderReversalService::class)->sync($order->id, 'early');

    $job = new \Gabha\RewardCoins\Jobs\AwardCoinsForOrder(new \Gabha\RewardCoins\DTOs\CoinEarningPayload(
        customerId: $customer,
        subtotal: 1000,
        discountAmount: 0,
        orderId: $order->id,
        categoryIds: [],
        orderIncrementId: (string) $order->increment_id,
    ));
    app()->call([$job, 'handle']);

    $lot = $this->rows($customer, TransactionType::Earned)->first();
    expect($lot->status)->toBe(TransactionStatus::Cancelled);
    expect($this->walletRow($customer)->pending_balance)->toBe(0);

    $this->assertConsistent($customer);
});

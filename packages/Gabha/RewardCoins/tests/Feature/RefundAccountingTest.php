<?php

use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Listeners\RevokeCoinsOnRefund;
use Gabha\RewardCoins\Sales\CoinAwareRefundRepository;
use Illuminate\Support\Facades\Http;
use Webkul\Sales\Repositories\RefundRepository;

beforeEach(function () {
    // The refund SMS listener must never reach the real gateway.
    Http::fake();
});

/**
 * Pre-coin value of a refund: what the customer is owed in cash + coin value.
 */
function refundGross($refund): float
{
    return round((float) $refund->base_grand_total + (float) $refund->base_coin_discount_amount, 4);
}

it('binds the coin-aware refund repository', function () {
    expect(app(RefundRepository::class))->toBeInstanceOf(CoinAwareRefundRepository::class);
});

it('leaves cash-only orders exactly as stock Bagisto refunds them', function () {
    $customer = $this->customer();
    $order    = $this->paidOrder($customer, [['price' => 400], ['price' => 600]], shipping: 50);
    [$a, $b]  = $order->items->all();

    $first  = $this->refund($order, [$a->id => 1]);
    $second = $this->refund($order->refresh(), [$b->id => 1], shipping: 50);

    expect((float) $first->base_grand_total)->toBe(400.0);
    expect((float) $second->base_grand_total)->toBe(650.0);
    expect($first->coins_refunded)->toBe(0);
    expect($second->coins_refunded)->toBe(0);
    expect((float) $order->refresh()->base_grand_total_refunded)->toBe(1050.0);
});

it('refunds cash net of the coin share and restores those coins on a partial refund', function () {
    $customer = $this->customer();
    $this->earnConfirmed($this->order($customer), 200);

    // ₹400 + ₹600 + ₹50 shipping = ₹1,050 pre-coin; 150 coins → ₹900 paid.
    $order = $this->paidOrder($customer, [['price' => 400], ['price' => 600]], shipping: 50, coins: 150);
    $this->redeem($order, 150);
    [$a] = $order->items->all();

    $refund = $this->refund($order, [$a->id => 1]);

    // 150 × 400 / 1050 = 57.14 → 57 coins; cash = 400 − 57.
    expect($refund->coins_refunded)->toBe(57);
    expect((float) $refund->base_coin_discount_amount)->toBe(57.0);
    expect((float) $refund->base_grand_total)->toBe(343.0);
    expect(refundGross($refund))->toBe(400.0);

    expect($this->rows($customer, TransactionType::Refunded)->sum('amount'))->toBe(57);
    expect($this->walletRow($customer)->balance)->toBe(50 + 57);

    $this->assertConsistent($customer);
});

it('never pays the coin value twice on a full refund', function () {
    $customer = $this->customer();
    $this->earnConfirmed($this->order($customer), 200);

    $order = $this->paidOrder($customer, [['price' => 400], ['price' => 600]], shipping: 50, coins: 150);
    $this->redeem($order, 150);
    $this->earnConfirmed($order, 90);
    [$a, $b] = $order->items->all();

    $refund = $this->refund($order, [$a->id => 1, $b->id => 1], shipping: 50);

    // Cash refunded = exactly what was paid; all 150 coins come back once.
    expect((float) $refund->base_grand_total)->toBe(900.0);
    expect($refund->coins_refunded)->toBe(150);

    $order->refresh();
    expect($order->status)->toBe('closed');
    expect((float) $order->base_grand_total_refunded)->toBe((float) $order->base_grand_total);

    // The queued closed-status reversal running afterwards adds nothing.
    (new \Gabha\RewardCoins\Jobs\ReverseCoinsForOrder($order->id))->handle(app(\Gabha\RewardCoins\Services\CoinOrderReversalService::class));

    expect($this->rows($customer, TransactionType::Refunded)->sum('amount'))->toBe(150);
    expect($this->rows($customer, TransactionType::Revoked)->sum('amount'))->toBe(90);
    expect($this->walletRow($customer)->balance)->toBe(200);
    expect($this->rows($customer, TransactionType::Redeemed)[0]->status)->toBe(TransactionStatus::Reversed);

    $this->assertConsistent($customer);
});

it('allocates coins against item-level discounts, tax and shipping', function () {
    $customer = $this->customer();
    $this->earnConfirmed($this->order($customer), 300);

    // Lines: ₹300 ×2 (+₹108 tax), ₹500 (+₹90 tax, −₹100 coupon); shipping ₹40 + ₹7.20 tax; 100 coins.
    $order = $this->paidOrder($customer, [
        ['price' => 300, 'tax' => 108, 'qty' => 2],
        ['price' => 500, 'tax' => 90, 'discount' => 100],
    ], shipping: 40, shippingTax: 7.2, coins: 100);
    $this->redeem($order, 100);
    [$a, $b] = $order->items->all();

    $preCoin = 600 + 108 + 500 + 90 - 100 + 40 + 7.2;   // 1245.20
    $paid    = $preCoin - 100;                          // 1145.20

    $r1 = $this->refund($order, [$a->id => 1]);
    $r2 = $this->refund($order->refresh(), [$b->id => 1]);
    $r3 = $this->refund($order->refresh(), [$a->id => 1], shipping: 40);

    // Each refund: cash + coin value == its pre-coin gross.
    expect(refundGross($r1))->toBe(354.0);            // 300 + 54 tax
    expect(refundGross($r2))->toBe(490.0);            // 500 + 90 − 100
    expect(refundGross($r3))->toBe(401.2);            // 300 + 54 + 40 + 7.20

    // Cumulative, whole coins, ends exactly at the redemption.
    expect($r1->coins_refunded)->toBe((int) round(100 * 354 / $preCoin));
    expect($r1->coins_refunded + $r2->coins_refunded + $r3->coins_refunded)->toBe(100);

    $cash = (float) $r1->base_grand_total + (float) $r2->base_grand_total + (float) $r3->base_grand_total;
    expect(round($cash, 2))->toBe(round($paid, 2));

    expect($this->rows($customer, TransactionType::Refunded)->sum('amount'))->toBe(100);

    $this->assertConsistent($customer);
});

it('keeps the cumulative result identical whatever the refund order', function (array $sequence) {
    $customer = $this->customer();
    $this->earnConfirmed($this->order($customer), 500);

    $order = $this->paidOrder($customer, [['price' => 333], ['price' => 333], ['price' => 334]], shipping: 0, coins: 199);
    $this->redeem($order, 199);
    $items = $order->items->values();

    $coins = $cash = 0;

    foreach ($sequence as $index) {
        $refund = $this->refund($order->refresh(), [$items[$index]->id => 1]);

        $coins += $refund->coins_refunded;
        $cash  += (float) $refund->base_grand_total;

        expect(refundGross($refund))->toBe((float) $items[$index]->base_total);
    }

    expect($coins)->toBe(199);
    expect(round($cash, 2))->toBe(801.0);
    expect($this->rows($customer, TransactionType::Refunded)->sum('amount'))->toBe(199);

    $this->assertConsistent($customer);
})->with([
    'in order' => [[0, 1, 2]],
    'reversed' => [[2, 1, 0]],
    'mixed'    => [[1, 2, 0]],
]);

it('ignores a duplicate refund event', function () {
    $customer = $this->customer();
    $this->earnConfirmed($this->order($customer), 200);

    $order = $this->paidOrder($customer, [['price' => 400], ['price' => 600]], shipping: 50, coins: 150);
    $this->redeem($order, 150);
    [$a] = $order->items->all();

    $refund = $this->refund($order, [$a->id => 1]);

    app(RevokeCoinsOnRefund::class)->handle($refund);
    app(RevokeCoinsOnRefund::class)->handle($refund);

    expect($this->rows($customer, TransactionType::Refunded)->sum('amount'))->toBe(57);
    expect(\Webkul\Sales\Models\Refund::query()->where('order_id', $order->id)->count())->toBe(1);

    $this->assertConsistent($customer);
});

it('keeps manual adjustments as pure cash outside the coin share', function () {
    $customer = $this->customer();
    $this->earnConfirmed($this->order($customer), 200);

    $order = $this->paidOrder($customer, [['price' => 400], ['price' => 600]], shipping: 50, coins: 150);
    $this->redeem($order, 150);
    [$a] = $order->items->all();

    $refund = $this->refund($order, [$a->id => 1], adjustmentRefund: 25, adjustmentFee: 5);

    expect($refund->coins_refunded)->toBe(57);
    expect((float) $refund->base_grand_total)->toBe(400 - 57 + 25 - 5.0);
});

it('shows the coin share in the admin refund preview', function () {
    $customer = $this->customer();
    $order    = $this->paidOrder($customer, [['price' => 400], ['price' => 600]], shipping: 50, coins: 150);
    [$a]      = $order->items->all();

    $totals = app(RefundRepository::class)->getOrderItemsRefundSummary([
        'items'             => [$a->id => 1],
        'shipping'          => 0,
        'adjustment_refund' => 0,
        'adjustment_fee'    => 0,
    ], $order->id);

    expect($totals['coin_discount']['coins'])->toBe(57);
    expect((float) $totals['discount']['price'])->toBe(57.0);
    expect((float) $totals['grand_total']['price'])->toBe(343.0);
});

it('restores a refunded coin whose original lot has expired as already expired', function () {
    $customer = $this->customer();
    $source   = $this->earnConfirmed($this->order($customer), 100);

    $this->travel(200)->days();
    $newer = $this->earnConfirmed($this->order($customer), 40);

    $order = $this->paidOrder($customer, [['price' => 500]], coins: 100);
    $this->redeem($order, 100);
    [$a] = $order->items->all();

    // Past the source lot's expiry, the order is refunded.
    $this->travel(170)->days();
    $this->refund($order, [$a->id => 1]);

    $restored = $this->rows($customer, TransactionType::Refunded);
    expect($restored->sum('amount'))->toBe(100);
    expect($restored[0]->status)->toBe(TransactionStatus::Expired);
    expect($restored[0]->remaining)->toBe(0);
    expect($restored[0]->expires_at->equalTo($source->expires_at))->toBeTrue();

    // Never spendable, and the newer coins are untouched.
    expect($this->walletRow($customer)->balance)->toBe(40);
    expect($newer->refresh()->remaining)->toBe(40);

    $this->assertConsistent($customer);
});

/*
 * Part-delivered orders (typically COD): some lines invoiced, the rest
 * cancelled before invoicing. Cancelled items count like refunded ones.
 *
 * Fixture: A ₹400 (invoiced), B ₹600 (not invoiced), no shipping,
 * 150 coins redeemed → paid ₹850, pre-coin ₹1,000. 100 coins earned.
 */

it('reverses coins for items cancelled before invoicing', function () {
    $customer = $this->customer();
    $order    = $this->partDeliveredOrder($customer);

    expect(app(\Webkul\Sales\Repositories\OrderRepository::class)->cancel($order->id))->toBeTrue();

    expect($order->refresh()->status)->not->toBe('canceled');

    // B is 60% of the merchandise and of the pre-coin payable.
    expect($this->rows($customer, TransactionType::Revoked)->sum('amount'))->toBe(60);
    expect($this->rows($customer, TransactionType::Refunded)->sum('amount'))->toBe(90);

    $this->assertConsistent($customer);
});

it('settles a part-cancelled order the same way whatever happens first', function (string $sequence) {
    $customer = $this->customer();
    $order    = $this->partDeliveredOrder($customer);
    $a        = $order->items->first();

    $cancel = fn () => app(\Webkul\Sales\Repositories\OrderRepository::class)->cancel($order->id);
    $refund = fn () => $this->refund($order->refresh(), [$a->id => 1]);

    $refundRow = null;

    if ($sequence === 'cancel then refund') {
        $cancel();
        $refundRow = $refund();
    } else {
        $refundRow = $refund();
        $cancel();
    }

    // Duplicate events after the fact change nothing.
    app(\Gabha\RewardCoins\Listeners\RevokeCoinsOnRefund::class)->handle($refundRow);
    app(\Gabha\RewardCoins\Services\CoinOrderReversalService::class)->sync($order->id, 'replay');

    // Everything given back: all earned coins revoked, all redeemed restored once.
    expect($this->rows($customer, TransactionType::Revoked)->sum('amount'))->toBe(100);
    expect($this->rows($customer, TransactionType::Refunded)->sum('amount'))->toBe(150);

    // Cash for A is net of A's coin share: 400 − round(150 × 400 / 1000).
    expect($refundRow->coins_refunded)->toBe(60);
    expect((float) $refundRow->base_grand_total)->toBe(340.0);

    // Wallet: 200 − 150 redeemed + 150 restored; earned 100 fully revoked.
    expect($this->walletRow($customer)->balance)->toBe(200);

    $this->assertConsistent($customer);
})->with(['cancel then refund', 'refund then cancel']);

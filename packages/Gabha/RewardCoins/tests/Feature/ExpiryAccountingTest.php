<?php

use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Models\CoinAllocation;
use Gabha\RewardCoins\Services\CoinExpiryService;
use Gabha\RewardCoins\Services\CoinOrderReversalService;

it('expires a fully unspent lot', function () {
    $customer = $this->customer();
    $lot      = $this->earnConfirmed($this->order($customer), 100);

    $this->travel(366)->days();

    $result = app(CoinExpiryService::class)->expireOldCoins();

    expect($result)->toBe(['lots' => 1, 'coins' => 100, 'failed' => 0]);
    expect($this->walletRow($customer)->balance)->toBe(0);
    expect($lot->refresh()->status)->toBe(TransactionStatus::Expired);
    expect($lot->remaining)->toBe(0);

    $expired = $this->rows($customer, TransactionType::Expired);
    expect($expired)->toHaveCount(1);
    expect($expired[0]->amount)->toBe(100);
    expect($expired[0]->operation_key)->toBe('expire:lot:'.$lot->id);

    $this->assertConsistent($customer);
});

it('expires only the unspent remainder of a partly spent lot', function () {
    $customer = $this->customer();
    $lot      = $this->earnConfirmed($this->order($customer), 100);

    $this->redeem($this->order($customer), 30);

    $this->travel(366)->days();
    app(CoinExpiryService::class)->expireOldCoins();

    expect($this->walletRow($customer)->balance)->toBe(0);
    expect($this->rows($customer, TransactionType::Expired)->sum('amount'))->toBe(70);
    expect($lot->refresh()->remaining)->toBe(0);

    $this->assertConsistent($customer);
});

it('expires an exhausted lot without touching the balance', function () {
    $customer = $this->customer();
    $lot      = $this->earnConfirmed($this->order($customer), 100);

    $this->redeem($this->order($customer), 100);

    $this->travel(366)->days();
    $result = app(CoinExpiryService::class)->expireOldCoins();

    expect($result['coins'])->toBe(0);
    expect($this->rows($customer, TransactionType::Expired))->toHaveCount(0);
    expect($lot->refresh()->status)->toBe(TransactionStatus::Expired);
    expect($this->walletRow($customer)->balance)->toBe(0);

    $this->assertConsistent($customer);
});

it('keeps newer coins when an old, already spent lot expires', function () {
    $customer = $this->customer();

    // Earn 100, spend all 100.
    $old = $this->earnConfirmed($this->order($customer), 100);
    $this->redeem($this->order($customer), 100);

    // 200 days later earn 50 more.
    $this->travel(200)->days();
    $new = $this->earnConfirmed($this->order($customer), 50);

    // The old lot reaches its expiry.
    $this->travel(166)->days();
    app(CoinExpiryService::class)->expireOldCoins();

    expect($old->refresh()->status)->toBe(TransactionStatus::Expired);
    expect($new->refresh()->status)->toBe(TransactionStatus::Confirmed);
    expect($new->remaining)->toBe(50);
    expect($this->walletRow($customer)->balance)->toBe(50);
    expect($this->rows($customer, TransactionType::Expired))->toHaveCount(0);

    $this->assertConsistent($customer);
});

it('spends the soonest-expiring lot first', function () {
    $customer = $this->customer();

    $first = $this->earnConfirmed($this->order($customer), 40);
    $this->travel(10)->days();
    $second = $this->earnConfirmed($this->order($customer), 40);

    $debit = $this->redeem($this->order($customer), 50);

    expect($first->refresh()->remaining)->toBe(0);
    expect($second->refresh()->remaining)->toBe(30);

    $allocations = CoinAllocation::query()->where('debit_transaction_id', $debit->id)->orderBy('id')->get();
    expect($allocations->pluck('credit_transaction_id')->all())->toBe([$first->id, $second->id]);
    expect($allocations->pluck('amount')->all())->toBe([40, 10]);

    $this->assertConsistent($customer);
});

it('expires what is left after a refund claw-back and preserves newer coins', function () {
    $customer = $this->customer();
    $order    = $this->order($customer);
    $lot      = $this->earnConfirmed($order, 100);

    $this->travel(30)->days();
    $newer = $this->earnConfirmed($this->order($customer), 20);

    // Half the merchandise is refunded: 50 coins clawed back from the lot.
    $order->update(['base_sub_total_refunded' => 500, 'base_grand_total_refunded' => 500]);
    app(CoinOrderReversalService::class)->sync($order->id, 'test');

    expect($lot->refresh()->remaining)->toBe(50);
    expect($this->walletRow($customer)->balance)->toBe(70);

    $this->travel(336)->days();
    app(CoinExpiryService::class)->expireOldCoins();

    expect($this->rows($customer, TransactionType::Expired)->sum('amount'))->toBe(50);
    expect($newer->refresh()->remaining)->toBe(20);
    expect($this->walletRow($customer)->balance)->toBe(20);

    $this->assertConsistent($customer);
});

it('is safe to run the expiry job twice', function () {
    $customer = $this->customer();
    $this->earnConfirmed($this->order($customer), 100);
    $this->earnConfirmed($this->order($customer), 10);

    $this->travel(366)->days();

    app(CoinExpiryService::class)->expireOldCoins();
    $second = app(CoinExpiryService::class)->expireOldCoins();

    expect($second)->toBe(['lots' => 0, 'coins' => 0, 'failed' => 0]);
    expect($this->rows($customer, TransactionType::Expired)->sum('amount'))->toBe(110);
    expect($this->walletRow($customer)->balance)->toBe(0);

    $this->assertConsistent($customer);
});

it('never expires pending coins', function () {
    $customer = $this->customer();
    $lot      = $this->earn($this->order($customer), 100);

    $this->travel(400)->days();
    app(CoinExpiryService::class)->expireOldCoins();

    expect($lot->refresh()->status)->toBe(TransactionStatus::Pending);
    expect($this->walletRow($customer)->pending_balance)->toBe(100);

    $this->assertConsistent($customer);
});

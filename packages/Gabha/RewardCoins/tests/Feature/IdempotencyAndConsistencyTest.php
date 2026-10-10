<?php

use Gabha\RewardCoins\DTOs\CoinEarningPayload;
use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Jobs\AwardCoinsForOrder;
use Gabha\RewardCoins\Models\CoinAllocation;
use Gabha\RewardCoins\Models\CoinTransaction;
use Gabha\RewardCoins\Repositories\CoinWalletRepository;
use Gabha\RewardCoins\Repositories\Contracts\CoinWalletRepositoryInterface;
use Gabha\RewardCoins\Services\CoinLotBackfillService;
use Gabha\RewardCoins\Services\CoinReconciliationService;
use Gabha\RewardCoins\Services\CoinRedemptionService;
use Gabha\RewardCoins\Services\CoinWalletService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

function awardJob(int $customerId, $order): AwardCoinsForOrder
{
    return new AwardCoinsForOrder(new CoinEarningPayload(
        customerId: $customerId,
        subtotal: 799,
        discountAmount: 0,
        orderId: (int) $order->id,
        categoryIds: [],
        orderIncrementId: (string) $order->increment_id,
    ));
}

it('credits an order once even if the earning job runs twice', function () {
    $customer = $this->customer();
    $order    = $this->order($customer);

    app()->call([awardJob($customer, $order), 'handle']);
    app()->call([awardJob($customer, $order), 'handle']);

    $earned = $this->rows($customer, TransactionType::Earned);
    expect($earned)->toHaveCount(1);
    expect($earned[0]->amount)->toBe(79);
    expect($earned[0]->operation_key)->toBe("earn:order:{$order->id}");
    expect($this->walletRow($customer)->pending_balance)->toBe(79);

    $this->assertConsistent($customer);
});

it('debits a redemption once even if it is processed twice', function () {
    $customer = $this->customer();
    $this->earnConfirmed($this->order($customer), 100);
    $order = $this->order($customer);

    $service = app(CoinRedemptionService::class);
    expect($service->redeem($customer, 40, $order->id)->success)->toBeTrue();
    expect($service->redeem($customer, 40, $order->id)->success)->toBeTrue();

    $redeemed = $this->rows($customer, TransactionType::Redeemed);
    expect($redeemed)->toHaveCount(1);
    expect($redeemed[0]->operation_key)->toBe("redeem:order:{$order->id}");

    $wallet = $this->walletRow($customer);
    expect($wallet->balance)->toBe(60);
    expect($wallet->lifetime_redeemed)->toBe(40);

    $this->assertConsistent($customer);
});

it('rolls the ledger, lots and wallet back together when a movement fails', function () {
    $customer = $this->customer();
    $lot      = $this->earnConfirmed($this->order($customer), 100);

    app()->bind(CoinWalletRepositoryInterface::class, fn () => new class(app(\Gabha\RewardCoins\Models\CustomerCoinWallet::class)) extends CoinWalletRepository {
        public function applyRedemption(int $customerId, int $amount): void
        {
            throw new RuntimeException('simulated failure');
        }
    });

    $order = $this->order($customer);

    expect(fn () => app(CoinWalletService::class)->debit($customer, 30, TransactionType::Redeemed, $order->id, '', 'redeem:order:'.$order->id))
        ->toThrow(RuntimeException::class, 'simulated failure');

    expect($this->rows($customer, TransactionType::Redeemed))->toHaveCount(0);
    expect(CoinAllocation::query()->count())->toBe(0);
    expect($lot->refresh()->remaining)->toBe(100);
    expect($this->walletRow($customer)->balance)->toBe(100);

    $this->assertConsistent($customer);
});

it('lets a job retry cleanly after a simulated failure', function () {
    $customer = $this->customer();
    $order    = $this->order($customer);

    app()->bind(CoinWalletRepositoryInterface::class, fn () => new class(app(\Gabha\RewardCoins\Models\CustomerCoinWallet::class)) extends CoinWalletRepository {
        public function incrementPending(int $customerId, int $amount): void
        {
            throw new RuntimeException('worker died');
        }
    });

    expect(fn () => app()->call([awardJob($customer, $order), 'handle']))->toThrow(RuntimeException::class);
    expect($this->rows($customer, TransactionType::Earned))->toHaveCount(0);

    app()->forgetInstance(CoinWalletRepositoryInterface::class);
    app()->bind(CoinWalletRepositoryInterface::class, CoinWalletRepository::class);

    app()->call([awardJob($customer, $order), 'handle']);
    app()->call([awardJob($customer, $order), 'handle']);

    expect($this->rows($customer, TransactionType::Earned))->toHaveCount(1);
    expect($this->walletRow($customer)->pending_balance)->toBe(79);

    $this->assertConsistent($customer);
});

it('treats an admin double-submit as one adjustment', function () {
    $customer = $this->customer();
    $key      = 'admin:'.\Illuminate\Support\Str::uuid();

    $this->wallet()->credit($customer, 25, TransactionType::Adjusted, null, 'goodwill', TransactionStatus::Confirmed, $key);
    $this->wallet()->credit($customer, 25, TransactionType::Adjusted, null, 'goodwill', TransactionStatus::Confirmed, $key);

    $deductKey = 'admin:'.\Illuminate\Support\Str::uuid();
    $this->wallet()->debit($customer, 10, TransactionType::Deducted, null, 'fix', $deductKey);
    $this->wallet()->debit($customer, 10, TransactionType::Deducted, null, 'fix', $deductKey);

    expect($this->walletRow($customer)->balance)->toBe(15);
    expect($this->rows($customer, TransactionType::Deducted))->toHaveCount(1);
    expect(TransactionType::Deducted->isCredit())->toBeFalse();

    $this->assertConsistent($customer);
});

it('rejects a duplicate operation key at the database level', function () {
    $customer = $this->customer();
    $this->earn($this->order($customer), 10);

    $row = CoinTransaction::query()->where('customer_id', $customer)->firstOrFail();

    expect(fn () => DB::table('coin_transactions')->insert([
        'customer_id'   => $customer,
        'type'          => 'earned',
        'status'        => 'pending',
        'amount'        => 10,
        'operation_key' => $row->operation_key,
        'created_at'    => now(),
        'updated_at'    => now(),
    ]))->toThrow(\Illuminate\Database\UniqueConstraintViolationException::class);
});

it('reports no mismatches for a consistent wallet', function () {
    $customer = $this->customer();
    $this->earnConfirmed($this->order($customer), 100);
    $this->earn($this->order($customer), 30);
    $this->redeem($this->order($customer), 45);

    $report = app(CoinReconciliationService::class)->run($customer);

    expect($report['wallets'])->toBe(1);
    expect($report['mismatches'])->toBe([]);
});

it('reports expected versus actual for tampered fixtures without fixing them', function () {
    $customer = $this->customer();
    $lot      = $this->earnConfirmed($this->order($customer), 100);
    $this->earn($this->order($customer), 30);

    DB::table('customer_coin_wallets')->where('customer_id', $customer)->update(['balance' => 90, 'pending_balance' => 31]);
    DB::table('coin_transactions')->where('id', $lot->id)->update(['remaining' => 95]);

    $report = app(CoinReconciliationService::class)->run($customer);

    $checks = collect($report['mismatches'])->keyBy('check');

    expect($checks->keys()->sort()->values()->all())->toBe(['I1 pending_balance', 'I2 balance >= lots', 'I3 lot remaining']);
    expect($checks['I1 pending_balance']['expected'])->toBe(30);
    expect($checks['I1 pending_balance']['actual'])->toBe(31);
    expect($checks['I2 balance >= lots']['actual'])->toBe(90);
    expect($checks['I3 lot remaining']['expected'])->toBe(100);
    expect($checks['I3 lot remaining']['actual'])->toBe(95);

    // Read-only: nothing was repaired.
    expect($this->walletRow($customer)->balance)->toBe(90);
    expect($lot->refresh()->remaining)->toBe(95);

    $this->artisan('reward-coins:reconcile', ['--customer' => $customer])->assertFailed();
});

it('backfills a legacy history only when the replay matches the wallet', function () {
    $customer = $this->customer();
    $earnA    = $this->order($customer);
    $earnB    = $this->order($customer);
    $spend    = $this->order($customer);

    // Pre-fix rows: no operation key, no remaining, no allocations.
    $a = CoinTransaction::query()->create(['customer_id' => $customer, 'type' => 'earned', 'status' => 'confirmed', 'amount' => 100, 'order_id' => $earnA->id, 'expires_at' => now()->addDays(100)]);
    $b = CoinTransaction::query()->create(['customer_id' => $customer, 'type' => 'earned', 'status' => 'confirmed', 'amount' => 50, 'order_id' => $earnB->id, 'expires_at' => now()->addDays(300)]);
    CoinTransaction::query()->create(['customer_id' => $customer, 'type' => 'redeemed', 'status' => 'confirmed', 'amount' => 120, 'order_id' => $spend->id]);
    DB::table('customer_coin_wallets')->insert(['customer_id' => $customer, 'balance' => 30, 'pending_balance' => 0, 'lifetime_earned' => 150, 'lifetime_redeemed' => 120, 'created_at' => now(), 'updated_at' => now()]);

    $service = app(CoinLotBackfillService::class);

    $dry = $service->backfill($customer, false);
    expect($dry['result'])->toBe('would apply');
    expect($a->refresh()->remaining)->toBeNull();

    $applied = $service->backfill($customer, true);
    expect($applied['result'])->toBe('applied');
    expect($a->refresh()->remaining)->toBe(0);
    expect($b->refresh()->remaining)->toBe(30);
    expect($this->walletRow($customer)->balance)->toBe(30);

    expect(app(CoinReconciliationService::class)->run($customer)['mismatches'])->toBe([]);
});

it('refuses to backfill a history that does not add up', function () {
    $customer = $this->customer();
    $a        = CoinTransaction::query()->create(['customer_id' => $customer, 'type' => 'earned', 'status' => 'confirmed', 'amount' => 100, 'order_id' => $this->order($customer)->id, 'expires_at' => now()->addDays(100)]);
    DB::table('customer_coin_wallets')->insert(['customer_id' => $customer, 'balance' => 60, 'pending_balance' => 0, 'lifetime_earned' => 100, 'lifetime_redeemed' => 0, 'created_at' => now(), 'updated_at' => now()]);

    $result = app(CoinLotBackfillService::class)->backfill($customer, true);

    expect($result['result'])->toBe('skipped');
    expect($result['reason'])->toContain('wallet holds 60');
    expect($a->refresh()->remaining)->toBeNull();
});

it('serialises movements for one customer on the wallet row lock', function () {
    $customer = $this->customer();
    $this->earnConfirmed($this->order($customer), 10);

    config(['database.connections.coins_second' => config('database.connections.'.config('database.default'))]);

    $second = DB::connection('coins_second');

    if ($second->getDriverName() !== 'mysql') {
        $this->markTestSkipped('Row-lock test needs MySQL.');
    }

    // This connection holds the wallet lock, as every coin movement does.
    DB::transaction(function () use ($customer, $second) {
        app(CoinWalletService::class)->lockWallet($customer);

        $second->statement('SET SESSION innodb_lock_wait_timeout = 1');

        expect(fn () => $second->table('customer_coin_wallets')
            ->where('customer_id', $customer)
            ->lockForUpdate()
            ->first()
        )->toThrow(QueryException::class, 'Lock wait timeout');
    });

    $second->disconnect();
});

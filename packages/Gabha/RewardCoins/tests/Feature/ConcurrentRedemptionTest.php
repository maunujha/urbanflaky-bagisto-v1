<?php

/*
 * Real parallel redemptions on COMMITTED data.
 *
 * Fixtures are written through a separate auto-committing connection (the
 * test's own connection is wrapped in a rolled-back transaction and is
 * invisible to other processes). Each round starts N worker processes that
 * each boot the application with their own DB connection, wait on a shared
 * file barrier, and then call CoinWalletService::debit() at the same moment
 * against one wallet. Everything created is deleted afterwards.
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Customer\Models\Customer;

const COINS_COMMITTED = 'coins_committed';

beforeEach(function () {
    config(['database.connections.'.COINS_COMMITTED => config('database.connections.'.config('database.default'))]);

    if (DB::connection(COINS_COMMITTED)->getDriverName() !== 'mysql') {
        $this->markTestSkipped('Concurrent redemption test needs MySQL/InnoDB.');
    }

    $this->createdCustomers = [];
});

afterEach(function () {
    $db  = DB::connection(COINS_COMMITTED);
    $ids = $this->createdCustomers ?? [];

    if ($ids !== []) {
        $txnIds = $db->table('coin_transactions')->whereIn('customer_id', $ids)->pluck('id');
        $db->table('coin_allocations')->whereIn('debit_transaction_id', $txnIds)->orWhereIn('credit_transaction_id', $txnIds)->delete();
        $db->table('coin_transactions')->whereIn('customer_id', $ids)->delete();
        $db->table('customer_coin_wallets')->whereIn('customer_id', $ids)->delete();
        $db->table('customers')->whereIn('id', $ids)->delete();
    }

    $db->disconnect();
});

/**
 * Commit a customer holding one confirmed lot of $balance coins.
 */
function committedWallet($test, int $balance): int
{
    $db = DB::connection(COINS_COMMITTED);

    $attributes = Customer::factory()->make()->getAttributes();
    $attributes['created_at'] = $attributes['updated_at'] = now();

    $customerId = (int) $db->table('customers')->insertGetId($attributes);
    $test->createdCustomers[] = $customerId;

    $db->table('coin_transactions')->insert([
        'customer_id'   => $customerId,
        'type'          => 'earned',
        'status'        => 'confirmed',
        'amount'        => $balance,
        'remaining'     => $balance,
        'operation_key' => 'concurrency:fixture:'.Str::uuid(),
        'expires_at'    => now()->addYear(),
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);

    $db->table('customer_coin_wallets')->insert([
        'customer_id'     => $customerId,
        'balance'         => $balance,
        'lifetime_earned' => $balance,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    return $customerId;
}

/**
 * Race $requests (coins each) against one wallet in parallel processes.
 *
 * @return array<int, array<string, mixed>>
 */
function raceRedemptions(int $customerId, array $requests): array
{
    $barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'coins-race-'.Str::uuid();
    $worker  = dirname(__DIR__).'/Concurrency/debit-worker.php';
    $env     = array_merge(getenv(), [
        'APP_ENV'          => 'testing',
        'DB_DATABASE'      => config('database.connections.'.config('database.default').'.database'),
        'CACHE_STORE'      => 'array',
        'QUEUE_CONNECTION' => 'sync',
        'SESSION_DRIVER'   => 'array',
    ]);

    $procs = [];

    foreach ($requests as $i => $coins) {
        $key   = sprintf('concurrency:%d:%d:%s', $customerId, $i, Str::random(6));
        $pipes = [];

        $procs[] = [
            'key'   => $key,
            'proc'  => proc_open([PHP_BINARY, $worker, (string) $customerId, (string) $coins, $key, $barrier], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), $env),
            'pipes' => $pipes,
        ];
    }

    // Release everyone at once, only after every worker has booted.
    $deadline = microtime(true) + 60;

    while (count(glob($barrier.'.ready.*')) < count($procs)) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Workers did not become ready in time.');
        }

        usleep(2000);
    }

    touch($barrier);

    $results = [];

    foreach ($procs as $p) {
        $out = stream_get_contents($p['pipes'][1]);
        $err = stream_get_contents($p['pipes'][2]);
        fclose($p['pipes'][1]);
        fclose($p['pipes'][2]);
        proc_close($p['proc']);

        $decoded = json_decode((string) $out, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Worker produced no result: '.trim($out.' '.$err));
        }

        $results[] = $decoded + ['key' => $p['key']];
    }

    @unlink($barrier);
    array_map('unlink', glob($barrier.'.ready.*') ?: []);

    return $results;
}

/**
 * Assert the committed state after a race.
 */
function assertRaceOutcome(int $customerId, int $balance, array $requests, array $results): int
{
    $db = DB::connection(COINS_COMMITTED);

    foreach ($results as $r) {
        expect($r['ok'] || $r['error'] === 'insufficient')->toBeTrue('Unexpected worker error: '.($r['error'] ?? ''));
    }

    $succeeded = array_values(array_filter($results, fn ($r) => $r['ok']));
    $spent     = 0;

    foreach ($results as $i => $r) {
        if ($r['ok']) {
            $spent += $requests[$i];
        }
    }

    // Never more than the balance.
    expect($spent)->toBeLessThanOrEqual($balance);

    $wallet = $db->table('customer_coin_wallets')->where('customer_id', $customerId)->first();
    expect((int) $wallet->balance)->toBe($balance - $spent);
    expect((int) $wallet->lifetime_redeemed)->toBe($spent);

    // One ledger row per successful debit, keyed; none for failures.
    $debits = $db->table('coin_transactions')->where('customer_id', $customerId)->where('type', 'redeemed')->get();
    expect($debits)->toHaveCount(count($succeeded));
    expect($debits->pluck('operation_key')->sort()->values()->all())
        ->toBe(collect($succeeded)->pluck('key')->sort()->values()->all());

    foreach ($debits as $debit) {
        $allocated = (int) $db->table('coin_allocations')->where('debit_transaction_id', $debit->id)->sum('amount');
        expect($allocated)->toBe((int) $debit->amount);
    }

    $remaining = (int) $db->table('coin_transactions')->where('customer_id', $customerId)->where('type', 'earned')->value('remaining');
    expect($remaining)->toBe($balance - $spent);

    return count($succeeded);
}

it('never debits more than the balance when two redemptions race', function () {
    $outcomes = [];

    for ($round = 0; $round < 10; $round++) {
        $customer = committedWallet($this, 100);
        $requests = [70, 70];

        $outcomes[] = assertRaceOutcome($customer, 100, $requests, raceRedemptions($customer, $requests));
    }

    // Exactly one of each pair can be funded.
    expect(array_unique($outcomes))->toBe([1]);
});

it('funds exactly what the balance allows when many redemptions race', function () {
    for ($round = 0; $round < 5; $round++) {
        $customer = committedWallet($this, 100);
        $requests = [30, 30, 30, 30, 30];

        expect(assertRaceOutcome($customer, 100, $requests, raceRedemptions($customer, $requests)))->toBe(3);
    }
});

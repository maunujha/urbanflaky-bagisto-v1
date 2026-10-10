<?php

/*
 * Concurrency worker for ConcurrentRedemptionTest.
 *
 * Boots the application in its own process (own DB connection), signals that
 * it is ready, waits for the shared start barrier, then attempts one
 * redemption through CoinWalletService::debit() and prints a JSON result.
 *
 * Usage: php debit-worker.php <customerId> <coins> <operationKey> <barrierFile>
 */

use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Exceptions\InsufficientCoinsException;
use Gabha\RewardCoins\Services\CoinWalletService;

[$script, $customerId, $coins, $operationKey, $barrier] = $argv;

$root = dirname(__DIR__, 5);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Open the DB connection before the race so connect time does not skew it.
Illuminate\Support\Facades\DB::select('select 1');

touch($barrier.'.ready.'.md5($operationKey));

$deadline = microtime(true) + 30;

while (! file_exists($barrier)) {
    if (microtime(true) > $deadline) {
        echo json_encode(['ok' => false, 'error' => 'barrier timeout']);
        exit(1);
    }

    usleep(500);
}

try {
    $row = app(CoinWalletService::class)->debit(
        customerId: (int) $customerId,
        amount: (int) $coins,
        type: TransactionType::Redeemed,
        note: 'concurrency test',
        operationKey: $operationKey,
    );

    echo json_encode(['ok' => true, 'id' => $row->id, 'key' => $operationKey]);
} catch (InsufficientCoinsException $e) {
    echo json_encode(['ok' => false, 'error' => 'insufficient', 'key' => $operationKey]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => get_class($e).': '.$e->getMessage(), 'key' => $operationKey]);
}

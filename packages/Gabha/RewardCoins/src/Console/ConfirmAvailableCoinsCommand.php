<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Console;

use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Repositories\Contracts\CoinTransactionRepositoryInterface;
use Gabha\RewardCoins\Services\CoinDeliveryService;
use Gabha\RewardCoins\Services\CoinWalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Sales\Models\OrderProxy;

/**
 * Daily coin release, in two idempotent steps:
 *
 *  1. Delivery reconciliation: opens the return window on delivered orders
 *     whose pending coins never got one (missed webhook, event-less save,
 *     crashed worker) — {@see CoinDeliveryService::reconcileMissingWindows()}.
 *  2. Confirmation: promotes pending coins whose window has elapsed
 *     (available_at <= now) to spendable, skipping any whose order has since
 *     been cancelled or closed.
 *
 * Streams in bounded chunks; each lot is confirmed in its own transaction via
 * {@see CoinWalletService::confirm()}, so a crash mid-run loses nothing and a
 * re-run resumes. `php artisan reward-coins:confirm-available`.
 */
class ConfirmAvailableCoinsCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'reward-coins:confirm-available';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Open missing delivery windows, then confirm pending coins whose return window has elapsed.';

    /**
     * Execute the console command.
     *
     * @param  CoinTransactionRepositoryInterface  $transactions
     * @param  CoinWalletService  $walletService
     * @param  CoinDeliveryService  $delivery
     * @return int
     */
    public function handle(
        CoinTransactionRepositoryInterface $transactions,
        CoinWalletService $walletService,
        CoinDeliveryService $delivery,
    ): int {
        if (! CoinSetting::isEnabled()) {
            $this->info('RewardCoins is disabled. Skipping.');

            return self::SUCCESS;
        }

        $batch = (int) config('reward_coins.batch_size', 200);

        $stamped = $delivery->reconcileMissingWindows($batch);

        $reverseStatuses = (array) config('reward_coins.reverse_on_statuses', ['canceled', 'closed']);

        $confirmed = $coins = $skipped = $failed = 0;

        foreach ($transactions->lazyAvailableForConfirmation($batch) as $lot) {
            try {
                $status = $lot->order_id
                    ? OrderProxy::modelClass()::query()->whereKey($lot->order_id)->value('status')
                    : null;

                if ($status !== null && in_array($status, $reverseStatuses, true)) {
                    $skipped++;

                    continue;
                }

                $walletService->confirm($lot);

                $confirmed++;
                $coins += (int) $lot->amount;
            } catch (Throwable $e) {
                $failed++;

                Log::error('RewardCoins: failed to confirm coin lot.', [
                    'transaction_id' => $lot->id,
                    'error'          => $e->getMessage(),
                ]);
            }
        }

        $this->info(sprintf(
            'Opened %d delivery window(s). Confirmed %d coin(s) across %d lot(s); skipped %d on reversed orders; %d failed.',
            $stamped,
            $coins,
            $confirmed,
            $skipped,
            $failed,
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}

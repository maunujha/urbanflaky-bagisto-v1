<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Listeners;

use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Repositories\Contracts\CoinTransactionRepositoryInterface;
use Gabha\RewardCoins\Services\CoinWalletService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Confirms a customer's pending coins once their order reaches a "delivered"
 * status (sales.order.update-status.after).
 *
 * Bagisto core has no `delivered` status — in this store delivery maps to
 * `completed` (see config `reward_coins.confirm_on_statuses`). Note: the
 * Shiprocket *delivery* webhook saves the order directly and does not fire this
 * event; admin status changes to `completed` do.
 */
class ConfirmCoinsOnDelivery implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly CoinTransactionRepositoryInterface $transactions,
        private readonly CoinWalletService $walletService,
    ) {
    }

    /**
     * Route this listener onto the dedicated coins queue.
     *
     * @return string
     */
    public function viaQueue(): string
    {
        return (string) config('reward_coins.queue', 'coins');
    }

    /**
     * Handle the order status-change event.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @return void
     */
    public function handle($order): void
    {
        if (! CoinSetting::isEnabled()) {
            return;
        }

        $confirmStatuses = (array) config('reward_coins.confirm_on_statuses', ['completed']);

        if (! in_array($order->status, $confirmStatuses, true)) {
            return;
        }

        foreach ($this->transactions->getPendingForOrder((int) $order->id) as $transaction) {
            $this->walletService->confirm($transaction);
        }
    }

    /**
     * Log a failed run.
     *
     * @param  mixed  $order
     * @param  Throwable  $e
     * @return void
     */
    public function failed($order, Throwable $e): void
    {
        Log::error('RewardCoins: failed to confirm coins on delivery.', [
            'order_id' => $order->id ?? null,
            'error'    => $e->getMessage(),
        ]);
    }
}

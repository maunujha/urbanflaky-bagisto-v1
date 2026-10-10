<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Listeners;

use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Services\CoinOrderReversalService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Adjusts coins when an order is refunded (sales.refund.save.after).
 *
 * Delegates to {@see CoinOrderReversalService::sync()}, which brings the order
 * to its cumulative target: pending earned coins are voided, confirmed earned
 * coins are clawed back in proportion to the merchandise refunded so far, and
 * redeemed coins are restored in proportion to the paid total refunded so far.
 * Repeated or duplicate refund events post nothing new.
 *
 * Runs synchronously inside the refund's DB transaction (it reads the order
 * totals Bagisto has just recalculated), so the coin adjustment commits or
 * rolls back with the refund itself.
 *
 * Coins are non-critical: any failure is logged and swallowed so a refund is
 * never blocked by coin bookkeeping; the queued status-change reversal and the
 * reconciliation command will surface what was missed.
 */
class RevokeCoinsOnRefund
{
    public function __construct(
        private readonly CoinOrderReversalService $reversal,
    ) {
    }

    /**
     * Handle the refund-saved event.
     *
     * @param  \Webkul\Sales\Contracts\Refund  $refund
     * @return void
     */
    public function handle($refund): void
    {
        if (! CoinSetting::isEnabled() || empty($refund->order_id)) {
            return;
        }

        try {
            $this->reversal->sync((int) $refund->order_id, sprintf('refund:%d', (int) $refund->id));
        } catch (Throwable $e) {
            Log::error('RewardCoins: failed to adjust coins on refund.', [
                'refund_id' => $refund->id ?? null,
                'order_id'  => $refund->order_id ?? null,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}

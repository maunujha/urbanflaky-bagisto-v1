<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Listeners;

use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Repositories\Contracts\CoinTransactionRepositoryInterface;
use Gabha\RewardCoins\Services\CoinWalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Adjusts coins when an order is refunded (sales.refund.save.after).
 *
 *  - Earned coins are clawed back proportionally to the refunded amount. Coins
 *    already confirmed (spendable) are debited from the balance (floored at
 *    zero); coins still pending are cancelled outright so the confirm-available
 *    sweep can never later re-credit a refunded order.
 *  - Redeemed coins are restored, proportionally, so the customer gets back what
 *    they spent on goods they are returning.
 *
 * Runs synchronously and wraps the whole adjustment in one DB transaction, so a
 * failure leaves no partial state and a retry cannot double-apply. Idempotent
 * across repeated/partial refunds via the per-order revoked/refunded totals.
 *
 * Coins are non-critical: any failure is logged and swallowed so a refund is
 * never blocked by coin bookkeeping.
 */
class RevokeCoinsOnRefund
{
    public function __construct(
        private readonly CoinTransactionRepositoryInterface $transactions,
        private readonly CoinWalletService $walletService,
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
        if (! CoinSetting::isEnabled()) {
            return;
        }

        $order = $refund->order;

        if (! $order || empty($order->customer_id)) {
            return;
        }

        try {
            DB::transaction(function () use ($refund, $order): void {
                $customerId = (int) $order->customer_id;
                $ratio      = $this->refundRatio($refund, $order);

                $this->clawBackEarned($order, $refund, $customerId, $ratio);
                $this->restoreRedeemed($order, $refund, $customerId, $ratio);
            });
        } catch (Throwable $e) {
            Log::error('RewardCoins: failed to adjust coins on refund.', [
                'refund_id' => $refund->id ?? null,
                'order_id'  => $order->id ?? null,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fraction of the order this refund represents (0..1), in base currency.
     *
     * Falls back to a full claw-back when totals are unusable (e.g. a zero-value
     * order), so a refund never silently leaves coins behind.
     *
     * @param  \Webkul\Sales\Contracts\Refund  $refund
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @return float
     */
    private function refundRatio($refund, $order): float
    {
        $orderTotal  = (float) $order->base_grand_total;
        $refundTotal = (float) $refund->base_grand_total;

        if ($orderTotal <= 0.0 || $refundTotal <= 0.0) {
            return 1.0;
        }

        return min(1.0, $refundTotal / $orderTotal);
    }

    /**
     * Claw back the order's earned coins, capped at what has not been revoked by
     * an earlier (partial) refund.
     *
     * AwardCoinsOnOrder records a single earned batch per order, so the order-wide
     * revoked total is the per-batch revoked total.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @param  \Webkul\Sales\Contracts\Refund  $refund
     * @param  int  $customerId
     * @param  float  $ratio
     * @return void
     */
    private function clawBackEarned($order, $refund, int $customerId, float $ratio): void
    {
        $orderId        = (int) $order->id;
        $alreadyRevoked = $this->transactions->sumAmountForOrder($orderId, TransactionType::Revoked);

        foreach ($this->transactions->getEarnedForOrder($orderId) as $earned) {
            $remaining = max(0, (int) $earned->amount - $alreadyRevoked);

            if ($remaining <= 0) {
                continue;
            }

            $isPending = $earned->status === TransactionStatus::Pending;

            // Pending (not-yet-spendable) coins on a refunded order are cancelled
            // in full; confirmed coins are clawed back proportionally.
            $revoke = $isPending
                ? $remaining
                : min($remaining, (int) ceil((int) $earned->amount * $ratio));

            if ($revoke <= 0) {
                continue;
            }

            $this->walletService->revoke(
                customerId: $customerId,
                amount: $revoke,
                type: TransactionType::Revoked,
                fromPending: $isPending,
                orderId: $orderId,
                note: sprintf(
                    'Clawed back %d coin(s) — refund #%d on order #%s.',
                    $revoke,
                    (int) $refund->id,
                    (string) ($order->increment_id ?? $orderId),
                ),
            );

            if ($isPending && $revoke >= $remaining) {
                $earned->status = TransactionStatus::Cancelled;
                $earned->save();
            }

            $alreadyRevoked += $revoke;

            Log::info(sprintf(
                'RewardCoins: revoked %d coin(s) from customer #%d for refund #%d.',
                $revoke,
                $customerId,
                (int) $refund->id,
            ));
        }
    }

    /**
     * Restore redeemed coins proportionally to the refund.
     *
     * Based on the confirmed redeemed coins still standing for the order: if a
     * cancellation already reversed them this is empty, so a cancel-then-refund
     * cannot double-credit. Capped by what an earlier refund already restored.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @param  \Webkul\Sales\Contracts\Refund  $refund
     * @param  int  $customerId
     * @param  float  $ratio
     * @return void
     */
    private function restoreRedeemed($order, $refund, int $customerId, float $ratio): void
    {
        $orderId  = (int) $order->id;
        $redeemed = (int) $this->transactions->getRedeemedForOrder($orderId)->sum('amount');

        if ($redeemed <= 0) {
            return;
        }

        $alreadyRestored = $this->transactions->sumAmountForOrder($orderId, TransactionType::Refunded);
        $restore         = min((int) ceil($redeemed * $ratio), max(0, $redeemed - $alreadyRestored));

        if ($restore <= 0) {
            return;
        }

        $this->walletService->credit(
            customerId: $customerId,
            amount: $restore,
            type: TransactionType::Refunded,
            orderId: $orderId,
            note: sprintf(
                'Restored %d redeemed coin(s) — refund #%d on order #%s.',
                $restore,
                (int) $refund->id,
                (string) ($order->increment_id ?? $orderId),
            ),
            status: TransactionStatus::Confirmed,
        );

        Log::info(sprintf(
            'RewardCoins: restored %d redeemed coin(s) to customer #%d for refund #%d.',
            $restore,
            $customerId,
            (int) $refund->id,
        ));
    }
}

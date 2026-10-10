<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Services;

use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Repositories\Contracts\CoinTransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Webkul\Sales\Models\OrderProxy;

/**
 * Brings an order's coins in line with how much of the order has been undone.
 *
 * One entry point for cancellation and refunds. Instead of applying each event
 * as a delta, it computes the *cumulative* amount that should have been
 * revoked/restored for the order's current state and posts only the
 * difference from what is already in the ledger. That makes it:
 *
 *  - idempotent: a duplicate event, queue retry or second listener finds
 *    nothing left to do;
 *  - order-independent: "closed" status and the refund event may arrive in
 *    either order, on either a sync or async queue, with the same end state;
 *  - drift-free: rounding happens once on the cumulative target, never per
 *    partial refund.
 *
 * Basis (see docs/REWARD-COINS-ACCOUNTING.md §Reversals):
 *  - Earned coins: 100% when the order is canceled or closed, otherwise
 *    ceil(earned × (refunded + cancelled merchandise) / base_sub_total) —
 *    they were earned on the merchandise subtotal.
 *  - Redeemed coins: the coin-funded part of everything given back — refunds
 *    plus items cancelled before invoicing — on one cumulative basis
 *    ({@see CoinRefundAllocator::restorableCoins()}). Each refund's cash
 *    already excludes its coin share, so nothing is returned twice. An order
 *    undone without any refund gets 100% back. Refunds created before coin
 *    shares were recorded fall back to the previous paid grand-total ratio.
 */
class CoinOrderReversalService
{
    public function __construct(
        private readonly CoinWalletService $walletService,
        private readonly CoinTransactionRepositoryInterface $ledger,
        private readonly CoinRefundAllocator $allocator,
    ) {
    }

    /**
     * Reconcile the order's earned and redeemed coins with its current state.
     *
     * @param  int  $orderId
     * @param  string  $trigger  What caused the call (audit trail only).
     * @return void
     */
    public function sync(int $orderId, string $trigger): void
    {
        $order = OrderProxy::modelClass()::query()->find($orderId);

        if (! $order || empty($order->customer_id)) {
            return;
        }

        $earnFraction = $this->earnFraction($order);
        $refunds      = DB::table('refunds')->where('order_id', $orderId)->get(['coins_refunded']);

        if ($earnFraction <= 0.0 && $refunds->isEmpty() && ! $this->isUndone($order) && $this->allocator->cancelledGross($order) <= 0.0) {
            return;
        }

        DB::transaction(function () use ($order, $earnFraction, $refunds, $trigger): void {
            $this->walletService->lockWallet((int) $order->customer_id);

            if ($earnFraction > 0.0) {
                $this->reverseEarned($order, $earnFraction, $trigger);
            }

            $this->restoreRedeemed($order, $refunds, $trigger);
        });
    }

    /**
     * Fraction of the order's earned coins that should be revoked.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @return float
     */
    public function earnFraction($order): float
    {
        if ($this->isUndone($order)) {
            return 1.0;
        }

        // Merchandise given back: refunded plus cancelled before invoicing.
        return $this->ratio(
            (float) $order->base_sub_total_refunded + $this->allocator->cancelledSubtotal($order),
            (float) $order->base_sub_total,
        );
    }

    /**
     * Coins of the redemption that should have been restored by now.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @param  \Illuminate\Support\Collection<int, object>  $refunds
     * @param  int  $redeemed
     * @return int
     */
    public function restoreTarget($order, $refunds, int $redeemed): int
    {
        if ($refunds->isEmpty() && $this->isUndone($order)) {
            // Undone without any refund (all cancelled): no cash went back,
            // so every coin does.
            return $redeemed;
        }

        if ($refunds->contains(fn ($r): bool => $r->coins_refunded === null)) {
            // Legacy refunds (before coin shares were recorded): previous policy.
            return $this->isUndone($order)
                ? $redeemed
                : $this->target($redeemed, $this->ratio((float) $order->base_grand_total_refunded, (float) $order->base_grand_total));
        }

        // Refund shares plus the share of items cancelled before invoicing,
        // on one cumulative basis so nothing is returned twice.
        return min($redeemed, $this->allocator->restorableCoins($order));
    }

    /**
     * Cancel the pending earned lot, or claw back the confirmed one up to the
     * cumulative target.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @param  float  $fraction
     * @param  string  $trigger
     * @return void
     */
    private function reverseEarned($order, float $fraction, string $trigger): void
    {
        $orderId = (int) $order->id;
        $lot     = $this->ledger->lockOrderRow($orderId, TransactionType::Earned);

        if (! $lot) {
            return;
        }

        // Pending coins on a cancelled/refunded order are voided in full
        // (existing policy: they were never spendable).
        if ($lot->status === TransactionStatus::Pending) {
            $this->walletService->cancelPendingLot(
                lot: $lot,
                operationKey: sprintf('reversal:order:%d:void-pending', $orderId),
                note: sprintf('Voided %d pending coin(s), order #%s %s.', (int) $lot->amount, $this->label($order), $this->reason($order)),
                meta: ['trigger' => $trigger, 'fraction' => $fraction],
            );

            return;
        }

        if (! in_array($lot->status, [TransactionStatus::Confirmed, TransactionStatus::Expired], true)) {
            return;
        }

        $target  = $this->target((int) $lot->amount, $fraction);
        $settled = $this->ledger->getOrderRows($orderId, TransactionType::Revoked)
            ->sum(fn ($row): int => (int) $row->amount + (int) ($row->meta['shortfall'] ?? 0));
        $owed = $target - (int) $settled;

        if ($owed <= 0) {
            return;
        }

        $this->walletService->revokeEarned(
            lot: $lot,
            amount: $owed,
            operationKey: sprintf('reversal:order:%d:revoke-to:%d', $orderId, $target),
            note: sprintf('Clawed back coins earned on order #%s (%s).', $this->label($order), $this->reason($order)),
            meta: ['trigger' => $trigger, 'fraction' => round($fraction, 6), 'target' => $target],
        );
    }

    /**
     * Restore redeemed coins up to the cumulative target.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @param  float  $fraction
     * @param  string  $trigger
     * @return void
     */
    private function restoreRedeemed($order, $refunds, string $trigger): void
    {
        $orderId    = (int) $order->id;
        $redemption = $this->ledger->lockOrderRow($orderId, TransactionType::Redeemed);

        if (! $redemption) {
            return;
        }

        $restored = $this->ledger->sumAmountForOrder($orderId, TransactionType::Refunded);

        // A redemption reversed before lot accounting was restored in place
        // without a `refunded` row; it is already fully settled.
        if ($redemption->status === TransactionStatus::Reversed && $restored === 0) {
            return;
        }

        $target = $this->restoreTarget($order, $refunds, (int) $redemption->amount);
        $owed   = $target - $restored;

        if ($owed > 0) {
            $restored += $this->walletService->restoreRedemption(
                redemption: $redemption,
                amount: $owed,
                operationKeyPrefix: sprintf('reversal:order:%d:restore-to:%d', $orderId, $target),
                note: sprintf('Restored coins redeemed on order #%s (%s).', $this->label($order), $this->reason($order)),
                meta: ['trigger' => $trigger, 'target' => $target],
            );
        }

        if ($restored >= (int) $redemption->amount && $redemption->status !== TransactionStatus::Reversed) {
            $redemption->status = TransactionStatus::Reversed;
            $redemption->save();
        }
    }

    /**
     * Cumulative coins owed for a fraction, rounded up once.
     *
     * @param  int  $amount
     * @param  float  $fraction
     * @return int
     */
    private function target(int $amount, float $fraction): int
    {
        // round() first so float noise (0.1 + 0.2) can never tip ceil() over.
        return min($amount, (int) ceil(round($amount * $fraction, 6)));
    }

    /**
     * part / whole clamped to 0..1 (a refund on a zero-value base is total).
     *
     * @param  float  $part
     * @param  float  $whole
     * @return float
     */
    private function ratio(float $part, float $whole): float
    {
        if ($part <= 0.0) {
            return 0.0;
        }

        if ($whole <= 0.0) {
            return 1.0;
        }

        return min(1.0, $part / $whole);
    }

    /**
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @return string
     */
    private function label($order): string
    {
        return (string) ($order->increment_id ?? $order->id);
    }

    /**
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @return string
     */
    private function reason($order): string
    {
        return $this->isUndone($order) ? (string) $order->status : 'refunded';
    }

    /**
     * Whether the whole order was undone (canceled / closed).
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @return bool
     */
    private function isUndone($order): bool
    {
        return in_array($order->status, (array) config('reward_coins.reverse_on_statuses', ['canceled', 'closed']), true);
    }
}

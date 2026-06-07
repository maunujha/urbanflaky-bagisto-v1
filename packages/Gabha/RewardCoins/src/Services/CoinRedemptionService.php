<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Services;

use Gabha\RewardCoins\DTOs\TransactionResult;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Exceptions\InsufficientCoinsException;
use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Repositories\Contracts\CoinTransactionRepositoryInterface;
use Gabha\RewardCoins\Repositories\Contracts\CoinWalletRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Owns the redemption side: how many coins may be spent, what they are worth,
 * applying a redemption, and undoing one on cancellation/refund.
 */
class CoinRedemptionService
{
    public function __construct(
        private readonly CoinWalletService $walletService,
        private readonly CoinWalletRepositoryInterface $wallets,
        private readonly CoinTransactionRepositoryInterface $ledger,
    ) {
    }

    /**
     * Whether the customer can redeem any coins against the given cart total.
     *
     * @param  int  $customerId
     * @param  float  $cartTotal
     * @return bool
     */
    public function canRedeem(int $customerId, float $cartTotal): bool
    {
        return CoinSetting::isEnabled()
            && $this->getRedeemableCoins($customerId, $cartTotal) > 0;
    }

    /**
     * Maximum coins spendable on this cart, honouring balance and both caps
     * (absolute per-order ceiling and percentage-of-order limit).
     *
     * @param  int  $customerId
     * @param  float  $cartTotal
     * @return int
     */
    public function getRedeemableCoins(int $customerId, float $cartTotal): int
    {
        $balance = $this->wallets->getBalance($customerId);

        if ($balance <= 0 || $cartTotal <= 0) {
            return 0;
        }

        $rupeePerCoin = $this->rupeePerCoin();

        // Resolved lazily (cached) so constructing this service never hits the DB.
        $settings = CoinSetting::active();

        $maxValueByOrderCap = (float) $settings->max_redemption_per_order;
        $maxValueByPercent  = $cartTotal * ((int) $settings->max_redemption_percent / 100);

        // Coins can never discount more than the order is worth.
        $maxValue = min($maxValueByOrderCap, $maxValueByPercent, $cartTotal);

        $maxCoinsByValue = (int) floor($maxValue / $rupeePerCoin);

        return max(0, min($balance, $maxCoinsByValue));
    }

    /**
     * Store-currency value of the given number of coins.
     *
     * @param  int  $coins
     * @return float
     */
    public function getDiscountValue(int $coins): float
    {
        return max(0, $coins) * $this->rupeePerCoin();
    }

    /**
     * Spend coins against an order.
     *
     * @param  int  $customerId
     * @param  int  $coins
     * @param  int  $orderId
     * @return TransactionResult
     */
    public function redeem(int $customerId, int $coins, int $orderId): TransactionResult
    {
        if (! CoinSetting::isEnabled()) {
            return TransactionResult::failed(trans('reward-coins::reward_coins.errors.disabled'));
        }

        if ($coins <= 0) {
            return TransactionResult::failed('No coins selected for redemption.');
        }

        try {
            $this->walletService->debit(
                customerId: $customerId,
                amount: $coins,
                type: TransactionType::Redeemed,
                orderId: $orderId,
                note: sprintf('Redeemed on order #%d', $orderId),
            );
        } catch (InsufficientCoinsException $e) {
            return TransactionResult::failed($e->getMessage());
        }

        return TransactionResult::succeeded(
            coinsAwarded: -$coins,
            message: sprintf('Redeemed %d coins (₹%s off).', $coins, number_format($this->getDiscountValue($coins), 2)),
        );
    }

    /**
     * Undo every coin movement tied to an order: restore redeemed coins and
     * cancel any still-pending earned coins. One atomic unit of work.
     *
     * @param  int  $orderId
     * @return void
     */
    public function reverse(int $orderId): void
    {
        DB::transaction(function () use ($orderId): void {
            foreach ($this->ledger->getRedeemedForOrder($orderId) as $transaction) {
                $this->wallets->revertRedemption((int) $transaction->customer_id, (int) $transaction->amount);
            }

            foreach ($this->ledger->getPendingForOrder($orderId) as $transaction) {
                $this->wallets->decrementPending((int) $transaction->customer_id, (int) $transaction->amount);
            }

            $this->ledger->reverseForOrder($orderId);
        });
    }

    /**
     * Configured store-currency value of one coin (floored to a sane minimum).
     *
     * @return float
     */
    private function rupeePerCoin(): float
    {
        return max(0.01, (float) config('reward_coins.rupee_per_coin', 1));
    }
}

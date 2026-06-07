<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Services;

use Gabha\RewardCoins\DTOs\TransactionResult;
use Gabha\RewardCoins\DTOs\ValidationResult;
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
     * Fully validate a redemption request, returning a structured, user-facing
     * result. Centralises every redemption rule so the controller, form request
     * and any future caller share one source of truth (no silent failures).
     *
     * Measured against the pre-coin cart total in store (base) currency.
     *
     * @param  int    $customerId
     * @param  int    $coinsRequested
     * @param  float  $cartTotal
     * @return ValidationResult
     */
    public function validateRedemption(int $customerId, int $coinsRequested, float $cartTotal): ValidationResult
    {
        // 1. Program enabled.
        if (! CoinSetting::isEnabled()) {
            return ValidationResult::fail(
                'feature_disabled',
                trans('reward-coins::reward_coins.redeem.feature-disabled'),
            );
        }

        // 2. Authenticated customer (guests have no wallet).
        if ($customerId <= 0) {
            return ValidationResult::fail(
                'not_authenticated',
                trans('reward-coins::reward_coins.redeem.not-authenticated'),
            );
        }

        // 3. Minimum order value to redeem (0 = no floor).
        $minOrder = (float) config('reward_coins.min_order_for_redemption', 0);

        if ($minOrder > 0 && $cartTotal < $minOrder) {
            return ValidationResult::fail(
                'order_too_small',
                trans('reward-coins::reward_coins.redeem.order-too-small', [
                    'min' => core()->formatBasePrice($minOrder),
                ]),
            );
        }

        // 4. Customer holds spendable coins.
        $balance = $this->wallets->getBalance($customerId);

        if ($balance <= 0) {
            return ValidationResult::fail(
                'no_coins',
                trans('reward-coins::reward_coins.redeem.no-coins'),
            );
        }

        if ($coinsRequested > $balance) {
            return ValidationResult::fail(
                'insufficient_coins',
                trans('reward-coins::reward_coins.redeem.insufficient-coins', [
                    'balance' => number_format($balance),
                ]),
                $this->getRedeemableCoins($customerId, $cartTotal),
            );
        }

        // 5. Per-order coverage caps (absolute ceiling + percentage of order).
        $rupeePerCoin = $this->rupeePerCoin();
        $settings     = CoinSetting::active();
        $percent      = (int) $settings->max_redemption_percent;

        $maxValue = min(
            (float) $settings->max_redemption_per_order,
            $cartTotal * ($percent / 100),
            $cartTotal,
        );

        $maxCoins = max(0, (int) floor($maxValue / $rupeePerCoin));

        // Integer comparison is exact: requesting more coins than $maxCoins is
        // exactly requesting more rupee value than $maxValue.
        if ($coinsRequested > $maxCoins) {
            return ValidationResult::fail(
                'exceeds_max_coverage',
                trans('reward-coins::reward_coins.redeem.exceeds-max-coverage', [
                    'coins'   => number_format($maxCoins),
                    'percent' => $percent,
                    'amount'  => core()->formatBasePrice($maxValue),
                ]),
                $maxCoins,
            );
        }

        // 6. Coins may never cover the whole order (at least the smallest cash
        // amount must remain). Only reachable when the caps allow 100% coverage.
        if ($coinsRequested * $rupeePerCoin >= $cartTotal) {
            $allowed = max(0, min($maxCoins, (int) ceil($cartTotal / $rupeePerCoin) - 1));

            return ValidationResult::fail(
                'order_would_be_free',
                trans('reward-coins::reward_coins.redeem.would-be-free', [
                    'coins' => number_format($allowed),
                ]),
                $allowed,
            );
        }

        return ValidationResult::pass($maxCoins);
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

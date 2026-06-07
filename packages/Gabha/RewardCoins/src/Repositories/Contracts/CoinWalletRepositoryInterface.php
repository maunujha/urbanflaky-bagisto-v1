<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Repositories\Contracts;

use Gabha\RewardCoins\Models\CustomerCoinWallet;

/**
 * Data-access contract for customer coin wallets.
 *
 * Each method is a single, atomic balance mutation (one row, one UPDATE).
 * Composing several of these into a consistent unit of work — together with
 * the ledger write — is the job of the services, which wrap them in a DB
 * transaction.
 *
 * Pairs that touch the lifetime counters are coupled deliberately: only earned
 * coins are ever `pending`, so pending moves always mirror `lifetime_earned`;
 * redemption moves always mirror `lifetime_redeemed`.
 */
interface CoinWalletRepositoryInterface
{
    /**
     * Fetch the customer's wallet, creating an empty one on first access.
     *
     * @param  int  $customerId
     * @return CustomerCoinWallet
     */
    public function getOrCreate(int $customerId): CustomerCoinWallet;

    /**
     * Current spendable (confirmed) balance.
     *
     * @param  int  $customerId
     * @return int
     */
    public function getBalance(int $customerId): int;

    /**
     * balance += amount.
     *
     * @param  int  $customerId
     * @param  int  $amount
     * @return void
     */
    public function incrementBalance(int $customerId, int $amount): void;

    /**
     * balance -= amount.
     *
     * @param  int  $customerId
     * @param  int  $amount
     * @return void
     */
    public function decrementBalance(int $customerId, int $amount): void;

    /**
     * pending_balance += amount, lifetime_earned += amount.
     *
     * @param  int  $customerId
     * @param  int  $amount
     * @return void
     */
    public function incrementPending(int $customerId, int $amount): void;

    /**
     * pending_balance -= amount, lifetime_earned -= amount.
     *
     * (Addition to the base spec: needed to claw back a pending earn when an
     * order is cancelled before the coins confirm.)
     *
     * @param  int  $customerId
     * @param  int  $amount
     * @return void
     */
    public function decrementPending(int $customerId, int $amount): void;

    /**
     * Move matured coins: pending_balance -= amount, balance += amount.
     *
     * @param  int  $customerId
     * @param  int  $amount
     * @return void
     */
    public function confirmPending(int $customerId, int $amount): void;

    /**
     * Spend coins: balance -= amount, lifetime_redeemed += amount.
     *
     * (Addition to the base spec: keeps the redeemed lifetime counter in sync
     * with the balance debit in a single atomic update.)
     *
     * @param  int  $customerId
     * @param  int  $amount
     * @return void
     */
    public function applyRedemption(int $customerId, int $amount): void;

    /**
     * Undo a redemption: balance += amount, lifetime_redeemed -= amount.
     *
     * (Addition to the base spec: mirror of applyRedemption for reversals.)
     *
     * @param  int  $customerId
     * @param  int  $amount
     * @return void
     */
    public function revertRedemption(int $customerId, int $amount): void;
}

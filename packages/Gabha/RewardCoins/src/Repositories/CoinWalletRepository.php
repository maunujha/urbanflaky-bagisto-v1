<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Repositories;

use Gabha\RewardCoins\Models\CustomerCoinWallet;
use Gabha\RewardCoins\Repositories\Contracts\CoinWalletRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent-backed implementation of the wallet repository.
 *
 * Every mutator issues a single, atomic `col = col ± amount` UPDATE (using
 * column-relative SQL so concurrent updates don't lose writes). Unsigned
 * columns are floored at zero to stay valid even if a caller over-debits.
 */
class CoinWalletRepository implements CoinWalletRepositoryInterface
{
    /**
     * @param  CustomerCoinWallet  $model  Injected so the data layer is swappable.
     */
    public function __construct(private readonly CustomerCoinWallet $model)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getOrCreate(int $customerId): CustomerCoinWallet
    {
        return $this->model->newQuery()->firstOrCreate(['customer_id' => $customerId]);
    }

    /**
     * {@inheritDoc}
     */
    public function getBalance(int $customerId): int
    {
        // Pure read: never materialises a wallet (this runs on every storefront
        // page via the header composer). Write paths use getOrCreate().
        return (int) ($this->model->newQuery()
            ->where('customer_id', $customerId)
            ->value('balance') ?? 0);
    }

    /**
     * {@inheritDoc}
     */
    public function incrementBalance(int $customerId, int $amount): void
    {
        $this->mutate($customerId, ['balance' => $this->add('balance', $amount)]);
    }

    /**
     * {@inheritDoc}
     */
    public function decrementBalance(int $customerId, int $amount): void
    {
        $this->mutate($customerId, ['balance' => $this->subtract('balance', $amount)]);
    }

    /**
     * {@inheritDoc}
     */
    public function incrementPending(int $customerId, int $amount): void
    {
        $this->mutate($customerId, [
            'pending_balance' => $this->add('pending_balance', $amount),
            'lifetime_earned' => $this->add('lifetime_earned', $amount),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function decrementPending(int $customerId, int $amount): void
    {
        $this->mutate($customerId, [
            'pending_balance' => $this->subtract('pending_balance', $amount),
            'lifetime_earned' => $this->subtract('lifetime_earned', $amount),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function confirmPending(int $customerId, int $amount): void
    {
        $this->mutate($customerId, [
            'pending_balance' => $this->subtract('pending_balance', $amount),
            'balance'         => $this->add('balance', $amount),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function applyRedemption(int $customerId, int $amount): void
    {
        $this->mutate($customerId, [
            'balance'           => $this->subtract('balance', $amount),
            'lifetime_redeemed' => $this->add('lifetime_redeemed', $amount),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function revertRedemption(int $customerId, int $amount): void
    {
        $this->mutate($customerId, [
            'balance'           => $this->add('balance', $amount),
            'lifetime_redeemed' => $this->subtract('lifetime_redeemed', $amount),
        ]);
    }

    /**
     * Ensure the wallet row exists, then apply a column-relative update.
     *
     * @param  int  $customerId
     * @param  array<string, \Illuminate\Database\Query\Expression>  $columns
     * @return void
     */
    private function mutate(int $customerId, array $columns): void
    {
        $this->getOrCreate($customerId);

        $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->update($columns);
    }

    /**
     * Build a `col + amount` SQL expression.
     */
    private function add(string $column, int $amount): \Illuminate\Database\Query\Expression
    {
        return DB::raw(sprintf('%s + %d', $column, max(0, $amount)));
    }

    /**
     * Build a zero-floored `col - amount` SQL expression (safe on unsigned cols).
     */
    private function subtract(string $column, int $amount): \Illuminate\Database\Query\Expression
    {
        return DB::raw(sprintf('GREATEST(CAST(%s AS SIGNED) - %d, 0)', $column, max(0, $amount)));
    }
}

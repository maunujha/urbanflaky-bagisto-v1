<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\ViewComposers;

use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Repositories\Contracts\CoinWalletRepositoryInterface;
use Illuminate\View\View;

/**
 * Exposes the logged-in customer's spendable coin balance to the storefront
 * layout as `$coinBalance`, so any header/template can drop in
 * `<x-reward-coins::coin-badge :balance="$coinBalance" />`.
 *
 * Guests (and a disabled program) resolve to 0 with no query.
 */
class CoinBalanceComposer
{
    public function __construct(
        private readonly CoinWalletRepositoryInterface $wallets,
    ) {
    }

    /**
     * Bind the coin balance onto the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view): void
    {
        $view->with('coinBalance', $this->balance());
    }

    /**
     * Resolve the logged-in customer's spendable coin balance.
     *
     * Guests — and a disabled program — resolve to 0 with no query. A customer
     * with no wallet row yet also resolves to 0 (the repository read never
     * materialises a row).
     *
     * @return int
     */
    public function balance(): int
    {
        $customer = auth()->guard('customer')->user();

        if (! $customer || ! CoinSetting::isEnabled()) {
            return 0;
        }

        return $this->wallets->getBalance((int) $customer->id);
    }
}

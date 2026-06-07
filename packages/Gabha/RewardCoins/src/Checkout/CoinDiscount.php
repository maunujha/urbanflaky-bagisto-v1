<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Checkout;

use Gabha\RewardCoins\Http\Controllers\Shop\CoinController;
use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Services\CoinRedemptionService;

/**
 * Cart-total collector for the coin-redemption discount.
 *
 * Bagisto 2.4 has no pluggable total-collector interface — totals are computed
 * monolithically in {@see \Webkul\Checkout\Cart::collectTotals()}. The supported,
 * core-free extension point is the `checkout.cart.collect.totals.after` event,
 * which fires with the freshly recomputed cart. This collector listens there and
 * folds the staged coin redemption into the cart as a negative adjustment on both
 * the discount and the grand total, so the value flows straight through to the
 * order (OrderResource copies these fields verbatim) and to the payment amount.
 *
 * It is intentionally idempotent: collectTotals() rebuilds every total from the
 * line items on each run *before* dispatching, so re-applying the coin discount
 * on every collect never compounds.
 */
class CoinDiscount
{
    /**
     * Session key holding the coin count this collector actually applied.
     *
     * Distinct from {@see CoinController::SESSION_KEY} (the customer's *request*):
     * the collector clamps the request down to what is currently redeemable and
     * records the effective count here, so the order listener debits exactly what
     * was discounted — never more.
     */
    public const EFFECTIVE_KEY = 'reward_coins.effective';

    public function __construct(
        private readonly CoinRedemptionService $redemption,
    ) {
    }

    /**
     * Apply the staged coin redemption to the recomputed cart totals.
     *
     * @param  \Webkul\Checkout\Contracts\Cart  $cart  The cart dispatched by collectTotals().
     * @return void
     */
    public function handle($cart): void
    {
        // Drop any stale effective count; resolveDiscount() re-sets it only when applied.
        session()->forget(self::EFFECTIVE_KEY);

        $discount = $this->resolveDiscount($cart);

        if ($discount <= 0.0) {
            return;
        }

        $cart->discount_amount = round((float) $cart->discount_amount + $discount, 2);
        $cart->base_discount_amount = round((float) $cart->base_discount_amount + $discount, 2);

        $cart->grand_total = round((float) $cart->grand_total - $discount, 2);
        $cart->base_grand_total = round((float) $cart->base_grand_total - $discount, 2);

        // collectTotals() already persisted the pre-coin totals; save the adjustment.
        $cart->save();
    }

    /**
     * Resolve the store-currency coin discount for the given cart, recording the
     * effective coin count in the session as a side effect.
     *
     * Returns zero when the program is disabled, the cart is a guest cart, nothing
     * is staged, or nothing is currently redeemable.
     *
     * @param  \Webkul\Checkout\Contracts\Cart  $cart
     * @return float
     */
    private function resolveDiscount($cart): float
    {
        if (! CoinSetting::isEnabled()) {
            return 0.0;
        }

        $stagedCoins = $this->stagedCoins();

        if ($stagedCoins <= 0) {
            return 0.0;
        }

        // Guest carts have no wallet to debit.
        $customerId = (int) ($cart->customer_id ?? 0);

        if ($customerId <= 0) {
            return 0.0;
        }

        /*
         * The pre-coin total: collectTotals() rebuilds the grand total from the
         * line items and dispatches *before* this collector folds anything in, so
         * the value read here never includes a coin discount.
         */
        $preCoinTotal = (float) $cart->base_grand_total;

        // Clamp the request to what is actually redeemable now, so the displayed
        // saving can never exceed the wallet debit performed at placement.
        $coins = min($stagedCoins, $this->redemption->getRedeemableCoins($customerId, $preCoinTotal));

        if ($coins <= 0) {
            return 0.0;
        }

        // Never discount the cart below zero.
        $discount = min($this->redemption->getDiscountValue($coins), $preCoinTotal);

        if ($discount <= 0.0) {
            return 0.0;
        }

        session()->put(self::EFFECTIVE_KEY, $coins);

        return $discount;
    }

    /**
     * The coin count the customer has staged for redemption (0 when none).
     *
     * @return int
     */
    private function stagedCoins(): int
    {
        return (int) session(CoinController::SESSION_KEY.'.coins', 0);
    }
}

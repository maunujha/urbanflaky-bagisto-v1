<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Http\Controllers\Shop;

use Gabha\RewardCoins\Checkout\CoinDiscount;
use Gabha\RewardCoins\Http\Requests\RedeemCoinsRequest;
use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Repositories\Contracts\CoinTransactionRepositoryInterface;
use Gabha\RewardCoins\Repositories\Contracts\CoinWalletRepositoryInterface;
use Gabha\RewardCoins\Services\CoinRedemptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Webkul\Checkout\Facades\Cart;
use Webkul\Shop\Http\Controllers\Controller;

/**
 * Storefront coin endpoints: the My Coins account page and the checkout
 * apply/remove actions that stage how many coins the customer wants to spend.
 *
 * Staging is intentionally side-effect-light: applying only records the chosen
 * coin count in the session and returns the rupee preview. The actual ledger
 * debit happens at order placement via CoinRedemptionService::redeem().
 */
class CoinController extends Controller
{
    /**
     * Session key holding the customer's staged redemption.
     */
    public const SESSION_KEY = 'reward_coins.applied';

    /**
     * Window (days) used for the "expiring soon" summary card.
     */
    private const EXPIRING_WINDOW_DAYS = 30;

    public function __construct(
        private readonly CoinWalletRepositoryInterface $wallets,
        private readonly CoinTransactionRepositoryInterface $transactions,
        private readonly CoinRedemptionService $redemption,
    ) {
    }

    /**
     * My Coins account page: balances + paginated history.
     *
     * @return View
     */
    public function index(): View
    {
        $customerId = (int) auth()->guard('customer')->id();

        return view('reward-coins::shop.account.coins', [
            'wallet'       => $this->wallets->getOrCreate($customerId),
            'transactions' => $this->transactions->getForCustomer($customerId),
            'expiringSoon' => $this->transactions->expiringSoonTotal($customerId, self::EXPIRING_WINDOW_DAYS),
        ]);
    }

    /**
     * Stage a coin redemption for the current cart and return the live preview.
     *
     * Staging is recomputed through the cart-total pipeline so the response
     * reflects the real, clamped discount and the new grand total the customer
     * will pay — without mutating any Bagisto core file.
     *
     * @param  RedeemCoinsRequest  $request
     * @return JsonResponse
     */
    public function apply(RedeemCoinsRequest $request): JsonResponse
    {
        if (! CoinSetting::isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => trans('reward-coins::reward_coins.errors.disabled'),
            ], 422);
        }

        $requestedCoins = (int) $request->validated()['coins'];
        $customerId     = (int) auth()->guard('customer')->id();

        /*
         * Establish the true pre-coin cart total: drop any prior staging and
         * recompute so the eligibility check measures against the real order value.
         */
        session()->forget([self::SESSION_KEY, CoinDiscount::EFFECTIVE_KEY]);
        Cart::collectTotals();

        $cart = Cart::getCart();

        if (! $cart) {
            return response()->json([
                'success' => false,
                'message' => trans('reward-coins::reward_coins.errors.no-cart'),
            ], 422);
        }

        $cartTotal = (float) $cart->base_grand_total;

        if (! $this->redemption->canRedeem($customerId, $cartTotal)) {
            return response()->json([
                'success' => false,
                'message' => trans('reward-coins::reward_coins.errors.insufficient-coins'),
            ], 422);
        }

        // Clamp the request to what is redeemable on this cart (caps + balance).
        $coins = min($requestedCoins, $this->redemption->getRedeemableCoins($customerId, $cartTotal));

        if ($coins <= 0) {
            return response()->json([
                'success' => false,
                'message' => trans('reward-coins::reward_coins.errors.insufficient-coins'),
            ], 422);
        }

        // Stage, then let the cart-total collector fold the discount into the cart.
        session()->put(self::SESSION_KEY, ['coins' => $coins]);
        Cart::collectTotals();

        $cart     = Cart::getCart();
        $applied  = (int) session(CoinDiscount::EFFECTIVE_KEY, $coins);
        $discount = $this->redemption->getDiscountValue($applied);

        return response()->json([
            'success'         => true,
            'coins'           => $applied,
            'discount'        => core()->formatBasePrice($discount),
            'discount_value'  => $discount,
            'newTotal'        => core()->formatBasePrice($cart->grand_total),
            'new_total_value' => (float) $cart->grand_total,
            'message'         => trans('reward-coins::reward_coins.checkout.applied', ['coins' => $applied]),
        ]);
    }

    /**
     * Remove any staged coin redemption and recompute the cart total.
     *
     * @return JsonResponse
     */
    public function remove(): JsonResponse
    {
        session()->forget([self::SESSION_KEY, CoinDiscount::EFFECTIVE_KEY]);

        // Recompute so the cart total drops the coin discount immediately.
        Cart::collectTotals();

        $cart = Cart::getCart();

        return response()->json([
            'success'         => true,
            'newTotal'        => $cart ? core()->formatBasePrice($cart->grand_total) : null,
            'new_total_value' => $cart ? (float) $cart->grand_total : null,
            'message'         => trans('reward-coins::reward_coins.checkout.removed'),
        ]);
    }
}

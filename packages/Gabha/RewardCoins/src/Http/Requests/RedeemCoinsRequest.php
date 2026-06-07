<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Http\Requests;

use Gabha\RewardCoins\Checkout\CoinDiscount;
use Gabha\RewardCoins\Services\CoinRedemptionService;
use Illuminate\Foundation\Http\FormRequest;
use Webkul\Checkout\Facades\Cart;

/**
 * Validates a checkout coin-redemption request.
 *
 * Beyond the basic shape, two domain guards run: the requested coins must not
 * exceed the customer's balance, nor the amount actually redeemable against the
 * current cart (both caps are folded into getRedeemableCoins()).
 */
class RedeemCoinsRequest extends FormRequest
{
    /**
     * Only authenticated customers may redeem.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return (bool) auth()->guard('customer')->check();
    }

    /**
     * Validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'coins' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail): void {
                    $customerId = (int) auth()->guard('customer')->id();

                    $redeemable = app(CoinRedemptionService::class)
                        ->getRedeemableCoins($customerId, $this->cartTotal());

                    if ((int) $value > $redeemable) {
                        $fail(trans('reward-coins::reward_coins.errors.insufficient-coins'));
                    }
                },
            ],
        ];
    }

    /**
     * Pre-coin cart total the redemption is measured against.
     *
     * Any coin discount already folded into the cart by the total collector is
     * added back, so re-applying (e.g. dragging the slider) always validates
     * against the true order value rather than the already-discounted one.
     *
     * @return float
     */
    private function cartTotal(): float
    {
        $cart = Cart::getCart();

        $total = (float) ($cart?->base_grand_total ?? 0.0);

        $applied = (int) session(CoinDiscount::EFFECTIVE_KEY, 0);

        if ($applied > 0) {
            $total += app(CoinRedemptionService::class)->getDiscountValue($applied);
        }

        return $total;
    }
}

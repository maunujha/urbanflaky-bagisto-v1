<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the *shape* of a checkout coin-redemption request.
 *
 * Domain eligibility (balance, caps, minimum order, coverage) is enforced by
 * {@see \Gabha\RewardCoins\Services\CoinRedemptionService::validateRedemption()}
 * so the controller can return a single structured error (code + message +
 * redeemable max) the widget can act on — rather than a generic validation 422.
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
            'coins' => ['required', 'integer', 'min:1'],
        ];
    }
}

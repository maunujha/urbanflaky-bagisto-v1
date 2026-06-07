<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Listeners;

use Gabha\RewardCoins\Checkout\CoinDiscount;
use Gabha\RewardCoins\Http\Controllers\Shop\CoinController;
use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Services\CoinRedemptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Performs the real wallet debit when an order is placed
 * (checkout.order.save.after).
 *
 * The rupee discount is already baked into the order total by {@see CoinDiscount}
 * at cart-collect time; this listener turns the staged redemption into an actual
 * ledger debit and stamps the coin count onto the order's `coins_redeemed` column.
 *
 * It runs synchronously (and is registered before {@see AwardCoinsOnOrder}) so the
 * redeemed count is committed before the earning listener reads it back.
 *
 * Coins are non-critical: any failure here is logged and swallowed so the sale
 * always completes — the customer has already paid the discounted total.
 */
class RedeemCoinsOnOrder
{
    public function __construct(
        private readonly CoinRedemptionService $redemption,
    ) {
    }

    /**
     * Handle the order-placed event.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @return void
     */
    public function handle($order): void
    {
        if (! CoinSetting::isEnabled()) {
            $this->clearSession();

            return;
        }

        // Guest orders have no wallet to debit.
        if (empty($order->customer_id)) {
            $this->clearSession();

            return;
        }

        $coins = $this->stagedCoins();

        if ($coins <= 0) {
            $this->clearSession();

            return;
        }

        try {
            DB::transaction(function () use ($order, $coins): void {
                $result = $this->redemption->redeem(
                    customerId: (int) $order->customer_id,
                    coins: $coins,
                    orderId: (int) $order->id,
                );

                if (! $result->success) {
                    // Roll the (no-op) transaction back; record nothing.
                    throw new RuntimeException($result->message);
                }

                $order->coins_redeemed = $coins;
                $order->save();
            });
        } catch (Throwable $e) {
            // Coins are non-critical — never block the sale.
            Log::error('RewardCoins: failed to redeem coins on order.', [
                'order_id'    => $order->id ?? null,
                'customer_id' => $order->customer_id ?? null,
                'coins'       => $coins,
                'error'       => $e->getMessage(),
            ]);
        }

        $this->clearSession();
    }

    /**
     * The effective coin count to debit: the amount the collector actually applied
     * to the cart total, falling back to the raw staged request.
     *
     * @return int
     */
    private function stagedCoins(): int
    {
        $effective = (int) session(CoinDiscount::EFFECTIVE_KEY, 0);

        if ($effective > 0) {
            return $effective;
        }

        return (int) session(CoinController::SESSION_KEY.'.coins', 0);
    }

    /**
     * Clear both the request and effective staging keys.
     *
     * @return void
     */
    private function clearSession(): void
    {
        session()->forget([CoinController::SESSION_KEY, CoinDiscount::EFFECTIVE_KEY]);
    }
}

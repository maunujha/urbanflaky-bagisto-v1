<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Listeners;

use Gabha\RewardCoins\DTOs\CoinEarningPayload;
use Gabha\RewardCoins\Jobs\AwardCoinsForOrder;
use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Services\CoinRedemptionService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Awards pending coins when an order is placed (checkout.order.save.after).
 *
 * Runs synchronously itself (it only reads scalar/relation data already
 * loaded on $order — cheap, and never serializes the order model), then
 * queues the actual wallet credit as {@see AwardCoinsForOrder}, carrying
 * only the fully-primitive {@see CoinEarningPayload}. Queueing the live
 * $order model directly used to crash with "Serialization of 'Closure' is
 * not allowed" — something in core Bagisto's order relation graph isn't
 * reliably serializable. Building the payload here and queueing that instead
 * sidesteps the crash while keeping the wallet write off the checkout
 * request's critical path.
 */
class AwardCoinsOnOrder
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
            return;
        }

        // Guest orders have no wallet to credit.
        if (empty($order->customer_id)) {
            return;
        }

        try {
            $payload = new CoinEarningPayload(
                customerId: (int) $order->customer_id,
                subtotal: (float) $order->sub_total,
                discountAmount: $this->merchandiseDiscount($order),
                orderId: (int) $order->id,
                categoryIds: $this->extractCategoryIds($order),
                orderIncrementId: (string) $order->increment_id,
            );

            AwardCoinsForOrder::dispatch($payload);
        } catch (Throwable $e) {
            Log::error('RewardCoins: failed to build the coin-earning payload for order.', [
                'order_id' => $order->id ?? null,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * Collect the distinct category ids across the order's items.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @return array<int, int>
     */
    private function extractCategoryIds($order): array
    {
        $categoryIds = [];

        foreach ($order->items as $item) {
            $product = $item->product;

            if (! $product) {
                continue;
            }

            foreach ($product->categories as $category) {
                $categoryIds[] = (int) $category->id;
            }
        }

        return array_values(array_unique($categoryIds));
    }

    /**
     * The order's discount excluding the coin-redemption portion.
     *
     * Coins are folded into the order's discount_amount by {@see CoinDiscount},
     * but earning must be driven only by real merchandise discounts (coupons,
     * cart rules) — so the coin value is backed out here. Keeps earning behaviour
     * identical whether or not the customer also spent coins.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @return float
     */
    private function merchandiseDiscount($order): float
    {
        $coinValue = $this->redemption->getDiscountValue((int) ($order->coins_redeemed ?? 0));

        return max(0.0, (float) $order->discount_amount - $coinValue);
    }
}

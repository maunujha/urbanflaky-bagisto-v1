<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Listeners;

use Gabha\RewardCoins\DTOs\CoinEarningPayload;
use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Services\CoinEarningCalculator;
use Gabha\RewardCoins\Services\CoinRedemptionService;
use Gabha\RewardCoins\Services\CoinWalletService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Awards pending coins when an order is placed (checkout.order.save.after).
 *
 * Deliberately NOT queued: the $order payload this event dispatches can carry
 * a non-serializable closure somewhere in its loaded relation graph (root
 * cause not in this package - surfaced elsewhere in the order lifecycle as
 * "Serialization of 'Closure' is not allowed" when Laravel builds the queued
 * job, before handle() ever runs, and crashes the whole event dispatch for
 * every listener on that event). Running inline avoids the queue's
 * serialize/unserialize round-trip entirely; the wallet credit is a single
 * lightweight DB write, not worth the queue risk.
 */
class AwardCoinsOnOrder
{
    public function __construct(
        private readonly CoinEarningCalculator $calculator,
        private readonly CoinWalletService $walletService,
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

            $coins = $this->calculator->calculate($payload);

            if ($coins <= 0) {
                return;
            }

            $this->walletService->credit(
                customerId: $payload->customerId,
                amount: $coins,
                type: TransactionType::Earned,
                orderId: $payload->orderId,
                note: sprintf('Earned on order #%s', $payload->orderIncrementId),
                status: TransactionStatus::Pending,
            );
        } catch (Throwable $e) {
            Log::error('RewardCoins: failed to award coins on order.', [
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

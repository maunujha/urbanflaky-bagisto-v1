<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Services;

use Illuminate\Support\Facades\DB;

/**
 * Splits an order's cart-level coin discount across what was given back:
 * refunded value (credit memos) and cancelled items that were never invoiced.
 *
 * The coin discount is applied to the whole cart (items, tax and shipping),
 * so it is attributed in proportion to value:
 *
 *   R   = coins_redeemed,  r = rupee_per_coin,  C = R × r
 *   P   = order.base_grand_total + C                 (pre-coin payable)
 *   g   = a refund's gross before its coin share: items + tax − item
 *         discounts + shipping (+ its tax); manual adjustment_refund /
 *         adjustment_fee are pure cash and excluded
 *   X   = cancelled gross: Σ qty_canceled × (unit price + unit tax − unit
 *         discount) over top-level items
 *
 *   coins_for(V) = min(R, round(R × min(1, V / P)))     whole coins, cumulative
 *
 *   A refund's coin share = coins_for(G_refunds incl. this one) − coins already
 *   attributed to earlier refunds. Its cash = g − share × r.
 *   Coins to restore      = max(Σ refund shares, coins_for(G_refunds + X)).
 *
 * Cumulative throughout: whatever the sequence of refunds and cancellations,
 * cash + coins handed back never exceed what was paid, rounding never
 * drifts, and giving everything back returns exactly R coins.
 */
class CoinRefundAllocator
{
    /**
     * Coins attributed to a refund whose gross (pre-coin) value is $gross.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @param  float  $gross
     * @param  int|null  $excludeRefundId  The refund being (re)calculated.
     * @return int
     */
    public function coinsFor($order, float $gross, ?int $excludeRefundId = null): int
    {
        if ((int) ($order->coins_redeemed ?? 0) <= 0 || $gross <= 0.0) {
            return 0;
        }

        $earlier = $this->refunds((int) $order->id, $excludeRefundId);

        $cumulative = $this->coinsForValue($order, $this->grossOf($earlier) + $gross);

        return max(0, $cumulative - (int) $earlier->sum('coins_refunded'));
    }

    /**
     * Total coins of the redemption that should be back with the customer for
     * everything refunded or cancelled so far.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @return int
     */
    public function restorableCoins($order): int
    {
        $refunds = $this->refunds((int) $order->id);

        return max(
            (int) $refunds->sum('coins_refunded'),
            $this->coinsForValue($order, $this->grossOf($refunds) + $this->cancelledGross($order)),
        );
    }

    /**
     * Whole coins attributable to $value of the pre-coin payable.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @param  float  $value
     * @return int
     */
    public function coinsForValue($order, float $value): int
    {
        $redeemed = (int) ($order->coins_redeemed ?? 0);

        if ($redeemed <= 0 || $value <= 0.0) {
            return 0;
        }

        $preCoinTotal = (float) $order->base_grand_total + $redeemed * $this->rupeePerCoin();

        if ($preCoinTotal <= 0.0) {
            return 0;
        }

        return min($redeemed, (int) round(round($redeemed * min(1.0, $value / $preCoinTotal), 6)));
    }

    /**
     * Pre-coin value of cancelled (never invoiced) items.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @return float
     */
    public function cancelledGross($order): float
    {
        return (float) DB::table('order_items')
            ->where('order_id', $order->id)
            ->whereNull('parent_id')
            ->where('qty_canceled', '>', 0)
            ->where('qty_ordered', '>', 0)
            ->get(['qty_canceled', 'qty_ordered', 'base_price', 'base_tax_amount', 'base_discount_amount'])
            ->sum(fn ($i): float => (float) $i->qty_canceled * (
                (float) $i->base_price
                + ((float) $i->base_tax_amount - (float) $i->base_discount_amount) / (float) $i->qty_ordered
            ));
    }

    /**
     * Merchandise subtotal of cancelled items (the earning basis).
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @return float
     */
    public function cancelledSubtotal($order): float
    {
        return (float) DB::table('order_items')
            ->where('order_id', $order->id)
            ->whereNull('parent_id')
            ->where('qty_canceled', '>', 0)
            ->get(['qty_canceled', 'base_price'])
            ->sum(fn ($i): float => (float) $i->qty_canceled * (float) $i->base_price);
    }

    /**
     * Store-currency value of one coin.
     *
     * @return float
     */
    public function rupeePerCoin(): float
    {
        return max(0.01, (float) config('reward_coins.rupee_per_coin', 1));
    }

    /**
     * The order's refunds (optionally excluding one).
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function refunds(int $orderId, ?int $excludeRefundId = null)
    {
        return DB::table('refunds')
            ->where('order_id', $orderId)
            ->when($excludeRefundId !== null, fn ($q) => $q->where('id', '<>', $excludeRefundId))
            ->get(['base_grand_total', 'base_adjustment_refund', 'base_adjustment_fee', 'base_coin_discount_amount', 'coins_refunded']);
    }

    /**
     * Σ pre-coin gross of refunds (cash + coin share, without adjustments).
     *
     * @param  \Illuminate\Support\Collection<int, object>  $refunds
     * @return float
     */
    private function grossOf($refunds): float
    {
        return (float) $refunds->sum(fn ($r): float => (float) $r->base_grand_total
            - (float) $r->base_adjustment_refund
            + (float) $r->base_adjustment_fee
            + (float) $r->base_coin_discount_amount);
    }
}

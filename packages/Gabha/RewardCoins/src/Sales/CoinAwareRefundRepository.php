<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Sales;

use Gabha\RewardCoins\Services\CoinRefundAllocator;
use Webkul\Sales\Repositories\RefundRepository;

/**
 * Bagisto's refund repository, made aware of the cart-level coin discount.
 *
 * Bagisto builds a refund's totals from its items (price + tax − item
 * discount) plus shipping and adjustments. The coin discount lives only on the
 * order (it is never spread onto items), so stock refunds return the full
 * pre-coin value in cash — and the coins are restored on top.
 *
 * This subclass, bound in place of the stock repository, overrides only the
 * two totals methods: each adds the refund's share of the coin discount
 * ({@see CoinRefundAllocator}) to the refund discount, lowering the cash grand
 * total that the Razorpay listener refunds and that Bagisto accumulates into
 * the order's refunded totals. Orders without redeemed coins are untouched.
 */
class CoinAwareRefundRepository extends RefundRepository
{
    /**
     * Persisted totals of a refund, net of its coin share.
     *
     * @param  \Webkul\Sales\Contracts\Refund  $refund
     * @return \Webkul\Sales\Contracts\Refund
     */
    public function collectTotals($refund)
    {
        $refund = parent::collectTotals($refund);

        $order = $refund->order;
        $gross = (float) $refund->base_grand_total
            - (float) $refund->base_adjustment_refund
            + (float) $refund->base_adjustment_fee;

        $coins = app(CoinRefundAllocator::class)->coinsFor($order, $gross, (int) $refund->id);
        $share = $coins * app(CoinRefundAllocator::class)->rupeePerCoin();

        $refund->coins_refunded            = $coins;
        $refund->base_coin_discount_amount = $share;

        if ($share > 0) {
            $converted = core()->convertPrice($share, $order->order_currency_code);

            $refund->base_discount_amount = (float) $refund->base_discount_amount + $share;
            $refund->discount_amount      = (float) $refund->discount_amount + $converted;
            $refund->base_grand_total     = (float) $refund->base_grand_total - $share;
            $refund->grand_total          = (float) $refund->grand_total - $converted;
        }

        $refund->save();

        return $refund;
    }

    /**
     * Admin preview / validation totals, net of the coin share the refund
     * would carry.
     *
     * @param  array  $data
     * @param  int  $orderId
     * @return array
     */
    public function getOrderItemsRefundSummary($data, $orderId)
    {
        $totals = parent::getOrderItemsRefundSummary($data, $orderId);

        $order = $this->orderRepository->find($orderId);
        $gross = (float) $totals['grand_total']['price']
            - (float) ($data['adjustment_refund'] ?? 0)
            + (float) ($data['adjustment_fee'] ?? 0);

        $coins = app(CoinRefundAllocator::class)->coinsFor($order, $gross);
        $share = $coins * app(CoinRefundAllocator::class)->rupeePerCoin();

        $totals['discount']['price']    += $share;
        $totals['grand_total']['price'] -= $share;

        $totals['coin_discount'] = ['price' => $share, 'coins' => $coins];

        foreach ($totals as $key => $item) {
            $totals[$key]['formatted_price'] = core()->formatBasePrice($item['price']);
        }

        return $totals;
    }
}

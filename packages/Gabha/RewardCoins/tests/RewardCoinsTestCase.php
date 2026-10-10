<?php

namespace Gabha\RewardCoins\Tests;

use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Jobs\AwardCoinsForOrder;
use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Models\CoinTransaction;
use Gabha\RewardCoins\Models\CustomerCoinWallet;
use Gabha\RewardCoins\Services\CoinReconciliationService;
use Gabha\RewardCoins\Services\CoinRedemptionService;
use Gabha\RewardCoins\Services\CoinWalletService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Customer\Models\Customer;
use Webkul\Sales\Models\Order;

/**
 * Shared fixtures for the coin accounting tests. Every test runs in a rolled
 * back transaction against the isolated test database, with the package's
 * default settings (1 coin / ₹10, 365-day expiry, 7-day return window).
 */
abstract class RewardCoinsTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['reward_coins.is_active' => true]);

        DB::table('coin_settings')->delete();
        CoinSetting::flushCache();
    }

    protected function wallet(): CoinWalletService
    {
        return app(CoinWalletService::class);
    }

    protected function customer(): int
    {
        return (int) Customer::factory()->create()->id;
    }

    /**
     * A customer order with clean, explicit totals (no refunds yet).
     */
    protected function order(int $customerId, array $attributes = []): Order
    {
        return Order::factory()->create(array_merge([
            'customer_id'               => $customerId,
            'customer_email'            => 'coins@example.com',
            'customer_first_name'       => 'Coin',
            'customer_last_name'        => 'Tester',
            'status'                    => 'processing',
            'base_currency_code'        => 'INR',
            'channel_currency_code'     => 'INR',
            'order_currency_code'       => 'INR',
            'sub_total'                 => 1000,
            'base_sub_total'            => 1000,
            'grand_total'               => 1000,
            'base_grand_total'          => 1000,
            'sub_total_refunded'        => 0,
            'base_sub_total_refunded'   => 0,
            'grand_total_refunded'      => 0,
            'base_grand_total_refunded' => 0,
        ], $attributes));
    }

    /**
     * A fully invoiced and shipped order with real line items, ready for
     * Bagisto's refund flow. Each line: unit `price` (ex-tax), line `tax`,
     * line `discount` (coupon / cart rule, item level) and `qty`. `$coins` is
     * the cart-level coin discount (₹1 per coin), applied to the order the way
     * the checkout collector does: on discount_amount and grand_total only.
     *
     * @param  array<int, array{price: float, tax?: float, discount?: float, qty?: int}>  $lines
     */
    protected function paidOrder(int $customerId, array $lines, float $shipping = 0, float $shippingTax = 0, int $coins = 0, array $uninvoiced = []): Order
    {
        $product = (new \Webkul\Faker\Helpers\Product)->getSimpleProductFactory()->create();

        $sub = $tax = $itemDiscount = 0.0;

        foreach ($lines as $line) {
            $sub          += $line['price'] * ($line['qty'] ?? 1);
            $tax          += $line['tax'] ?? 0;
            $itemDiscount += $line['discount'] ?? 0;
        }

        $grand = $sub + $tax + $shipping + $shippingTax - $itemDiscount - $coins;

        $totals = [
            'sub_total'           => $sub,
            'tax_amount'          => $tax + $shippingTax,
            'shipping_amount'     => $shipping,
            'shipping_invoiced'   => $shipping,
            'shipping_tax_amount' => $shippingTax,
            'discount_amount'     => $itemDiscount + $coins,
            'grand_total'         => $grand,
            'grand_total_invoiced' => $grand,
            'sub_total_invoiced'  => $sub,
        ];

        $attributes = ['coins_redeemed' => $coins, 'status' => 'processing'];

        foreach ($totals as $key => $value) {
            $attributes[$key]         = $value;
            $attributes['base_'.$key] = $value;
        }

        $order = $this->order($customerId, $attributes);

        $invoiceItems = [];

        foreach ($lines as $index => $line) {
            $qty = $line['qty'] ?? 1;

            $item = \Webkul\Sales\Models\OrderItem::factory()->create([
                'order_id'             => $order->id,
                'product_id'           => $product->id,
                'sku'                  => $product->sku,
                'name'                 => $product->name,
                'type'                 => 'simple',
                'qty_ordered'          => $qty,
                'qty_shipped'          => in_array($index, $uninvoiced, true) ? 0 : $qty,
                'price'                => $line['price'],
                'base_price'           => $line['price'],
                'price_incl_tax'       => $line['price'] + ($line['tax'] ?? 0) / $qty,
                'base_price_incl_tax'  => $line['price'] + ($line['tax'] ?? 0) / $qty,
                'total'                => $line['price'] * $qty,
                'base_total'           => $line['price'] * $qty,
                'total_invoiced'       => $line['price'] * $qty,
                'base_total_invoiced'  => $line['price'] * $qty,
                'tax_amount'           => $line['tax'] ?? 0,
                'base_tax_amount'      => $line['tax'] ?? 0,
                'discount_amount'      => $line['discount'] ?? 0,
                'base_discount_amount' => $line['discount'] ?? 0,
            ]);

            if (! in_array($index, $uninvoiced, true)) {
                $invoiceItems[$item->id] = $qty;
            }
        }

        \Webkul\Sales\Models\OrderAddress::factory()->create([
            'order_id'     => $order->id,
            'customer_id'  => $customerId,
            'address_type' => \Webkul\Sales\Models\OrderAddress::ADDRESS_TYPE_BILLING,
        ]);

        // A real invoice, as Razorpay creates on capture: Bagisto derives the
        // invoiced quantities and shipping that refunds are checked against.
        app(\Webkul\Sales\Repositories\InvoiceRepository::class)->create([
            'order_id' => $order->id,
            'invoice'  => ['items' => $invoiceItems],
        ], 'paid', 'processing');

        return $order->refresh();
    }

    /**
     * Part-delivered (COD-style) order: A ₹400 invoiced, B ₹600 not invoiced,
     * no shipping, 150 coins redeemed (paid ₹850, pre-coin ₹1,000) and 100
     * coins earned and confirmed. The customer also holds 200 other coins.
     */
    protected function partDeliveredOrder(int $customer): Order
    {
        $this->earnConfirmed($this->order($customer), 200);

        $order = $this->paidOrder($customer, [['price' => 400], ['price' => 600]], coins: 150, uninvoiced: [1]);
        $this->redeem($order, 150);
        $this->earnConfirmed($order, 100);

        return $order->refresh();
    }

    /**
     * Create a refund through Bagisto's repository (as the admin does).
     *
     * @param  array<int, int>  $items  order_item_id => qty
     */
    protected function refund(Order $order, array $items, float $shipping = 0, float $adjustmentRefund = 0, float $adjustmentFee = 0): \Webkul\Sales\Models\Refund
    {
        return app(\Webkul\Sales\Repositories\RefundRepository::class)->create([
            'order_id' => $order->id,
            'refund'   => [
                'items'             => $items,
                'shipping'          => $shipping,
                'adjustment_refund' => $adjustmentRefund,
                'adjustment_fee'    => $adjustmentFee,
            ],
        ]);
    }

    /**
     * Earn coins on an order as the live job does (pending lot).
     */
    protected function earn(Order $order, int $coins): CoinTransaction
    {
        return $this->wallet()->credit(
            customerId: (int) $order->customer_id,
            amount: $coins,
            type: TransactionType::Earned,
            orderId: (int) $order->id,
            note: 'test earn',
            status: TransactionStatus::Pending,
            operationKey: AwardCoinsForOrder::operationKey((int) $order->id),
        );
    }

    /**
     * Earn and immediately confirm.
     */
    protected function earnConfirmed(Order $order, int $coins): CoinTransaction
    {
        return $this->wallet()->confirm($this->earn($order, $coins))->refresh();
    }

    /**
     * Redeem coins against an order as checkout does.
     */
    protected function redeem(Order $order, int $coins): CoinTransaction
    {
        $result = app(CoinRedemptionService::class)->redeem((int) $order->customer_id, $coins, (int) $order->id);

        expect($result->success)->toBeTrue();

        $order->coins_redeemed = $coins;
        $order->save();

        return CoinTransaction::query()->where('operation_key', CoinRedemptionService::redemptionKey((int) $order->id))->firstOrFail();
    }

    protected function walletRow(int $customerId): CustomerCoinWallet
    {
        return CustomerCoinWallet::query()->where('customer_id', $customerId)->firstOrFail();
    }

    /**
     * Ledger rows of one type for a customer, oldest first.
     */
    protected function rows(int $customerId, TransactionType $type)
    {
        return CoinTransaction::query()
            ->where('customer_id', $customerId)
            ->where('type', $type->value)
            ->orderBy('id')
            ->get();
    }

    /**
     * Assert the customer's wallet, lots, allocations and ledger agree.
     */
    protected function assertConsistent(int $customerId): void
    {
        $report = app(CoinReconciliationService::class)->run($customerId);

        expect($report['mismatches'])->toBe([]);
        expect($report['legacy'])->toBe([]);
    }
}

<?php

namespace App\Services\Shiprocket;

use Illuminate\Support\Facades\Log;
use Webkul\Core\Models\CountryState;
use Webkul\Sales\Models\Order;

/**
 * Maps a Bagisto order to Shiprocket's orders/create/adhoc payload.
 *
 * Money: Shiprocket's order total (and so the COD amount the courier
 * collects) is sub_total − total_discount + shipping_charges. Bagisto
 * guarantees grand_total = sub_total_incl_tax + shipping_incl_tax − discount,
 * where discount already includes coupons and redeemed RewardCoins. Sending
 * tax-inclusive item prices therefore makes the two totals identical under
 * both inclusive and exclusive GST.
 */
class OrderPayloadBuilder
{
    public function __construct(protected ShiprocketSettings $settings) {}

    public function build(Order $order): array
    {
        $shipping = $order->shipping_address;
        $billing  = $order->billing_address ?? $shipping;

        $items       = [];
        $subTotal    = 0.0;
        $weightGrams = 0.0;

        foreach ($order->items as $item) {
            $qty = (int) $item->qty_ordered;

            if ($qty < 1) {
                continue;
            }

            $unitPrice = round((float) $item->base_price_incl_tax, 2);

            $items[] = [
                'name'          => mb_substr($item->name, 0, 200),
                'sku'           => $item->getTypeInstance()->getOrderedItem($item)->sku ?? $item->sku,
                'units'         => $qty,
                'selling_price' => $unitPrice,
                'discount'      => 0,
                'tax'           => round((float) $item->tax_percent, 2),
                'hsn'           => $this->settings->hsn(),
            ];

            $subTotal    += $unitPrice * $qty;
            $weightGrams += $this->itemWeightGrams($item) * $qty;
        }

        $shippingCharges = round((float) $order->base_shipping_amount_incl_tax, 2);
        $discount        = round((float) $order->base_discount_amount, 2);
        $subTotal        = round($subTotal, 2);

        $this->assertTotalsMatch($order, $subTotal, $shippingCharges, $discount);

        $sameAsBilling = $shipping && $billing
            && $shipping->postcode === $billing->postcode
            && trim($shipping->address) === trim($billing->address);

        return array_merge([
            'order_id'         => (string) $order->increment_id,
            'order_date'       => $order->created_at->copy()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i'),
            'pickup_location'  => $this->settings->pickupLocation(),
            'channel_id'       => '',
            'comment'          => '',
        ],
            $this->address('billing', $billing, $order),
            ['shipping_is_billing' => $sameAsBilling ? 1 : 0],
            $this->address('shipping', $shipping, $order),
            [
                'order_items'         => $items,
                'payment_method'      => $this->isCod($order) ? 'COD' : 'Prepaid',
                'shipping_charges'    => $shippingCharges,
                'giftwrap_charges'    => 0,
                'transaction_charges' => 0,
                'total_discount'      => $discount,
                'sub_total'           => $subTotal,
                'weight'              => max(round($weightGrams / 1000, 3), 0.1),
            ],
            $this->settings->packageDimensions(),
        );
    }

    public function isCod(Order $order): bool
    {
        return $order->payment?->method === 'cashondelivery';
    }

    /**
     * Store weights are in grams. Configurable lines carry the chosen
     * variant's weight; fall back to the variant, then the configured default.
     */
    protected function itemWeightGrams($item): float
    {
        $weight = (float) $item->weight;

        if ($weight <= 0) {
            $weight = (float) ($item->child?->product?->weight ?? $item->product?->weight ?? 0);
        }

        return $weight > 0 ? $weight : $this->settings->defaultItemWeightGrams();
    }

    protected function address(string $prefix, $address, Order $order): array
    {
        $fields = [
            'customer_name' => $address?->first_name ?: $order->customer_first_name,
            'last_name'     => $address?->last_name ?: $order->customer_last_name,
            'address'       => $this->line($address?->address),
            'address_2'     => '',
            'city'          => (string) $address?->city,
            'pincode'       => (string) $address?->postcode,
            'state'         => $this->stateName((string) $address?->state),
            'country'       => 'India',
            'email'         => $address?->email ?: (string) $order->customer_email,
            'phone'         => $this->phone((string) $address?->phone),
        ];

        $prefixed = [];

        foreach ($fields as $key => $value) {
            $prefixed[$prefix.'_'.$key] = $value;
        }

        return $prefixed;
    }

    /** Bagisto stores multi-line addresses newline-joined. */
    protected function line(?string $address): string
    {
        return trim(preg_replace('/\s*[\r\n]+\s*/', ', ', (string) $address), ', ');
    }

    /** Shiprocket wants the bare 10-digit Indian mobile number. */
    protected function phone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    }

    /** "RJ" → "Rajasthan". */
    protected function stateName(string $code): string
    {
        if ($code === '') {
            return '';
        }

        return CountryState::where('country_code', 'IN')->where('code', $code)->value('default_name') ?? $code;
    }

    /**
     * A mismatch here means the courier would collect the wrong COD amount.
     * Never block the push for it: log loudly instead, the order view shows it.
     */
    protected function assertTotalsMatch(Order $order, float $subTotal, float $shipping, float $discount): void
    {
        $shiprocketTotal = round($subTotal + $shipping - $discount, 2);

        if (abs($shiprocketTotal - (float) $order->base_grand_total) > 0.01) {
            Log::warning('Shiprocket payload total differs from order total', [
                'order'            => $order->increment_id,
                'order_total'      => (float) $order->base_grand_total,
                'shiprocket_total' => $shiprocketTotal,
            ]);
        }
    }
}

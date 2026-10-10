<?php

namespace App\Services\Shipping;

use App\Services\Shiprocket\ServiceabilityService;
use Webkul\Checkout\Contracts\Cart;

/**
 * The store's delivery and COD policy, read from admin configuration:
 *
 *  - Free Shipping (Sales → Shipping Methods → Free Shipping): on/off plus an
 *    optional minimum order amount. Blank minimum = free on every order.
 *  - Flat Rate: offered only when free shipping does not apply, so the
 *    customer always sees exactly one option.
 *  - Cash on Delivery: optional maximum order value, and an optional check
 *    that a courier can collect cash at the delivery pincode.
 *  - Every option disappears for a pincode no courier serves.
 *
 * Shiprocket being unreachable never blocks a sale: unknown = allowed.
 */
class DeliveryRules
{
    public function __construct(protected ServiceabilityService $serviceability) {}

    public function freeShippingEnabled(): bool
    {
        return (bool) core()->getConfigData('sales.carriers.free.active');
    }

    public function freeShippingMinimum(): ?float
    {
        $minimum = core()->getConfigData('sales.carriers.free.min_order_amount');

        return is_numeric($minimum) && (float) $minimum > 0 ? (float) $minimum : null;
    }

    public function qualifiesForFreeShipping(float $subtotal): bool
    {
        if (! $this->freeShippingEnabled()) {
            return false;
        }

        $minimum = $this->freeShippingMinimum();

        return $minimum === null || $subtotal >= $minimum;
    }

    /** Product subtotal the threshold is measured against: item prices incl. GST. */
    public function cartSubtotal(Cart $cart): float
    {
        return (float) $cart->base_sub_total_incl_tax;
    }

    public function cartDeliverable(?Cart $cart): bool
    {
        $postcode = (string) $cart?->shipping_address?->postcode;

        if ($postcode === '') {
            return true;
        }

        return ($this->serviceability->check($postcode, $this->cartWeightKg($cart))['serviceable'] ?? true) === true;
    }

    public function codMaximum(): ?float
    {
        $maximum = core()->getConfigData('sales.payment_methods.cashondelivery.max_order_total');

        return is_numeric($maximum) && (float) $maximum > 0 ? (float) $maximum : null;
    }

    public function codAllowed(Cart $cart): bool
    {
        $maximum = $this->codMaximum();

        if ($maximum !== null && (float) $cart->base_grand_total > $maximum) {
            return false;
        }

        if (! core()->getConfigData('sales.payment_methods.cashondelivery.check_pincode')) {
            return true;
        }

        $postcode = (string) $cart->shipping_address?->postcode;

        if ($postcode === '') {
            return true;
        }

        return ($this->serviceability->check($postcode, $this->cartWeightKg($cart))['cod'] ?? true) === true;
    }

    /**
     * What the product page promises before a cart exists.
     *
     * @return array{free: bool, free_over: ?float, fee: ?float, fee_per_unit: bool}
     */
    public function summary(): array
    {
        $free    = $this->freeShippingEnabled();
        $minimum = $free ? $this->freeShippingMinimum() : null;
        $flat    = (bool) core()->getConfigData('sales.carriers.flatrate.active');

        return [
            'free'         => $free && $minimum === null,
            'free_over'    => $minimum,
            'fee'          => $flat && ! ($free && $minimum === null) ? (float) core()->getConfigData('sales.carriers.flatrate.default_rate') : null,
            'fee_per_unit' => core()->getConfigData('sales.carriers.flatrate.type') === 'per_unit',
        ];
    }

    /**
     * Short product-page label: "Free delivery", "Free delivery over ₹999",
     * or null when every order pays the flat fee.
     */
    public function promise(): ?string
    {
        $summary = $this->summary();

        return match (true) {
            $summary['free']              => 'Free delivery',
            $summary['free_over'] !== null => 'Free delivery over ₹'.number_format($summary['free_over']),
            default                       => null,
        };
    }

    /** Grams in the store, kilograms for couriers. */
    protected function cartWeightKg(Cart $cart): float
    {
        $grams = 0.0;

        foreach ($cart->items as $item) {
            $grams += (float) $item->weight * $item->quantity;
        }

        return max($grams / 1000, 0.5);
    }
}

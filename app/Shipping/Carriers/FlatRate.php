<?php

namespace App\Shipping\Carriers;

use App\Services\Shipping\DeliveryRules;
use Webkul\Checkout\Facades\Cart;
use Webkul\Shipping\Carriers\FlatRate as BaseFlatRate;

/**
 * The paid fallback: hidden whenever free shipping applies, so checkout
 * always shows a single delivery option.
 */
class FlatRate extends BaseFlatRate
{
    public function isAvailable()
    {
        if (! parent::isAvailable()) {
            return false;
        }

        $cart  = Cart::getCart();
        $rules = app(DeliveryRules::class);

        return $cart
            && ! $rules->qualifiesForFreeShipping($rules->cartSubtotal($cart))
            && $rules->cartDeliverable($cart);
    }
}

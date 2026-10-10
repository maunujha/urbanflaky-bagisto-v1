<?php

namespace App\Shipping\Carriers;

use App\Services\Shipping\DeliveryRules;
use Webkul\Checkout\Facades\Cart;
use Webkul\Shipping\Carriers\Free as BaseFree;

/**
 * Free shipping with an optional minimum order amount, offered only where a
 * courier delivers.
 */
class Free extends BaseFree
{
    public function isAvailable()
    {
        $cart  = Cart::getCart();
        $rules = app(DeliveryRules::class);

        return $cart
            && $rules->qualifiesForFreeShipping($rules->cartSubtotal($cart))
            && $rules->cartDeliverable($cart);
    }
}

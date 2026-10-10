<?php

namespace App\Payment;

use App\Services\Shipping\DeliveryRules;
use Webkul\Payment\Payment\CashOnDelivery as BaseCashOnDelivery;

/**
 * COD within the admin-set maximum order value, and only where the courier
 * can collect cash.
 */
class CashOnDelivery extends BaseCashOnDelivery
{
    public function isAvailable()
    {
        if (! parent::isAvailable()) {
            return false;
        }

        return app(DeliveryRules::class)->codAllowed($this->cart);
    }
}

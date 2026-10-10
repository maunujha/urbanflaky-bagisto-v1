<?php

/*
| Extra admin fields for Bagisto's own configuration sections, keyed by the
| section they join. ShiprocketServiceProvider appends them at boot, so the
| core packages/Webkul/Admin/src/Config/system.php stays untouched.
|
| Read by App\Services\Shipping\DeliveryRules.
*/

return [
    /* Configure → Sales → Shipping Methods → Free Shipping */
    'sales.carriers.free' => [
        [
            'name'          => 'min_order_amount',
            'title'         => 'Minimum Order Amount',
            'info'          => 'Order subtotal (incl. GST) for free shipping. Leave blank for free shipping on every order. Below the minimum, customers see Flat Rate, so switch Flat Rate on when you set one (with Flat Rate off, every order ships free).',
            'type'          => 'text',
            'depends'       => 'active:1',
            'validation'    => 'decimal',
            'channel_based' => true,
            'locale_based'  => false,
        ],
    ],

    /* Configure → Sales → Payment Methods → Cash on Delivery */
    'sales.payment_methods.cashondelivery' => [
        [
            'name'          => 'max_order_total',
            'title'         => 'Maximum Order Value for COD',
            'info'          => 'Cash on Delivery is hidden above this order total (₹). Leave blank for no limit.',
            'type'          => 'text',
            'depends'       => 'active:1',
            'validation'    => 'decimal',
            'channel_based' => true,
            'locale_based'  => false,
        ], [
            'name'          => 'check_pincode',
            'title'         => 'Check COD Availability by Pincode',
            'info'          => 'Hide Cash on Delivery when no Shiprocket courier can collect cash at the delivery pincode.',
            'type'          => 'boolean',
            'depends'       => 'active:1',
            'channel_based' => true,
            'locale_based'  => false,
        ],
    ],
];

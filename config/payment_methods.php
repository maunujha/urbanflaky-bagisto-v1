<?php

/*
| Overrides the Payment package's Cash on Delivery with the store's version
| (maximum order value + pincode COD check). Keys here replace the package's
| entries of the same name.
*/

return [
    'cashondelivery' => [
        'class'            => App\Payment\CashOnDelivery::class,
        'code'             => 'cashondelivery',
        'title'            => 'Cash On Delivery',
        'description'      => 'Cash On Delivery',
        'active'           => true,
        'generate_invoice' => false,
        'sort'             => 6,
        'max_order_total'  => 2000,
        'check_pincode'    => true,
    ],
];

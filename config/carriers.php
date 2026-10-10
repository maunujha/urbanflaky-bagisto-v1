<?php

/*
| Overrides the Shipping package's carriers with the store's rule-aware
| versions (free-shipping threshold, single option, pincode serviceability).
| Keys here replace the package's entries of the same name.
*/

return [
    'flatrate' => [
        'code'         => 'flatrate',
        'title'        => 'Flat Rate',
        'description'  => 'Flat Rate Shipping',
        'active'       => false,
        'default_rate' => '10',
        'type'         => 'per_unit',
        'class'        => App\Shipping\Carriers\FlatRate::class,
    ],

    'free' => [
        'code'             => 'free',
        'title'            => 'Free Shipping',
        'description'      => 'Free Shipping',
        'active'           => false,
        'default_rate'     => '0',
        'min_order_amount' => '',
        'class'            => App\Shipping\Carriers\Free::class,
    ],
];

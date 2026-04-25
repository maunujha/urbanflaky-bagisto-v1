<?php

return [
    'flatrate' => [
        'code'         => 'flatrate',
        'title'        => 'Flat Rate',
        'description'  => 'Flat Rate Shipping',
        'active'       => false,
        'default_rate' => '10',
        'type'         => 'per_unit',
        'class'        => 'Webkul\Shipping\Carriers\FlatRate',
    ],

    'free' => [
        'code'         => 'free',
        'title'        => 'Free Shipping',
        'description'  => 'Free Shipping',
        'active'       => false,
        'default_rate' => '0',
        'class'        => 'Webkul\Shipping\Carriers\Free',
    ],

    'shiprocket' => [
        'code'        => 'shiprocket',
        'title'       => 'Shiprocket',
        'description' => 'Courier delivery via Shiprocket',
        'active'      => true,
        'class'       => 'Webkul\Shipping\Carriers\Shiprocket',
    ],
];

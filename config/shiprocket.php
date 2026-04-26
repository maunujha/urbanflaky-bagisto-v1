<?php

return [
    'email'           => env('SHIPROCKET_EMAIL'),
    'password'        => env('SHIPROCKET_PASSWORD'),
    'pickup_pincode'  => env('SHIPROCKET_PICKUP_PINCODE', '328001'),
    'pickup_location'  => env('SHIPROCKET_PICKUP_LOCATION', 'Primary'),
    'webhook_token'    => env('SHIPROCKET_WEBHOOK_TOKEN'),
];

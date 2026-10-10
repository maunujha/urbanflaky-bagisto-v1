<?php

/*
| Shiprocket integration.
|
| Credentials stay in .env (never in the admin DB). Everything under `pickup`,
| `automation` and `package` is also editable in Admin → Configure → Sales →
| Shiprocket: the admin field `sales.shiprocket.{group}.{field}` falls back to
| `shiprocket.{group}.{field}` here when it has never been saved.
*/

return [
    /* API user (Shiprocket → Settings → API), not the main account login. */
    'email'    => env('SHIPROCKET_EMAIL'),
    'password' => env('SHIPROCKET_PASSWORD'),

    /* Sent by Shiprocket in the x-api-key header of every tracking webhook. */
    'webhook_token' => env('SHIPROCKET_WEBHOOK_TOKEN'),

    'base_url' => 'https://apiv2.shiprocket.in/v1/external/',

    'timeout' => 15,

    /* Shiprocket tokens live 10 days; refresh well before that. */
    'token_ttl' => 8 * 24 * 3600,

    'pickup' => [
        /* Pickup location nickname exactly as registered in Shiprocket. */
        'location' => env('SHIPROCKET_PICKUP_LOCATION', 'home'),
        'pincode'  => env('SHIPROCKET_PICKUP_PINCODE', '328001'),
    ],

    'automation' => [
        /* Send every new order to Shiprocket (COD on placement, prepaid after capture). */
        'auto_push' => true,

        /* Let Shiprocket assign its recommended courier (Settings → Courier Priority). */
        'auto_assign_courier' => true,

        /* Book the pickup as soon as the AWB is assigned. Off = book from the order page. */
        'auto_schedule_pickup' => false,
    ],

    'package' => [
        /* Default box for apparel, in cm. */
        'length'  => 28,
        'breadth' => 22,
        'height'  => 4,

        /* Grams per unit when a product has no weight. */
        'default_item_weight' => 300,

        /* HSN 6109: T-shirts, singlets and other vests, knitted or crocheted. */
        'hsn' => '6109',
    ],

    /* Pincode serviceability rarely changes intraday. */
    'serviceability_cache_ttl' => 6 * 3600,

    'sync' => [
        /* Shipments polled per scheduler run. */
        'batch' => 50,

        /* Poll a shipment again once its last update is this old. */
        'stale_minutes' => 120,

        /* Live tracking on the public page refreshes at most this often. */
        'live_refresh_minutes' => 15,
    ],
];

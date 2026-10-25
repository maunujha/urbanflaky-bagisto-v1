<?php

return [
    /**
     * Skip attribute during product copy.
     *
     * Supported Relations: ['categories', 'inventories', 'customer_group_prices', 'images', 'videos', 'product_relations']
     *
     * Support Attributes: All Attributes (Example: 'sku', 'product_number', etc)
     */
    'copy' => [
        'skip_attributes' => [],
    ],

    /**
     * Product JSON-LD (Webkul\Shop\Helpers\StructuredData).
     *
     * delivery_time: business days from order to dispatch (handling) and from dispatch
     * to doorstep (transit). Google's merchant listings read these as a delivery
     * promise, so deliveryTime is only emitted when all four bounds are set.
     * Defaults are the store's stated service: dispatch within one day, 3–7 days in transit.
     */
    'structured_data' => [
        'delivery_time' => [
            'handling_min' => env('SCHEMA_HANDLING_DAYS_MIN', 0),
            'handling_max' => env('SCHEMA_HANDLING_DAYS_MAX', 1),
            'transit_min'  => env('SCHEMA_TRANSIT_DAYS_MIN', 3),
            'transit_max'  => env('SCHEMA_TRANSIT_DAYS_MAX', 7),
        ],
    ],
];

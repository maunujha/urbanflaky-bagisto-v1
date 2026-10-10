<?php

/*
| Admin → Configure → Sales → Shiprocket. Merged into Bagisto's `core` system
| config by ShiprocketServiceProvider. Unsaved fields fall back to
| config/shiprocket.php (same key without the leading "sales."). API
| credentials and the webhook token stay in .env.
*/

return [
    [
        'key'  => 'sales.shiprocket',
        'name' => 'Shiprocket',
        'info' => 'Order sync, courier assignment, pickup address and parcel defaults for Shiprocket.',
        'icon' => 'settings/shipping-method.svg',
        'sort' => 4,
    ], [
        'key'    => 'sales.shiprocket.automation',
        'name'   => 'Automation',
        'info'   => 'What happens automatically after an order is placed.',
        'sort'   => 1,
        'fields' => [
            [
                'name'          => 'auto_push',
                'title'         => 'Send new orders to Shiprocket',
                'info'          => 'COD orders are sent when placed; prepaid orders only after Razorpay confirms payment.',
                'type'          => 'boolean',
                'channel_based' => false,
                'locale_based'  => false,
            ], [
                'name'          => 'auto_assign_courier',
                'title'         => 'Assign the recommended courier automatically',
                'info'          => 'Shiprocket picks the courier using your Courier Priority setting (set it to "Recommended" in Shiprocket → Settings). You can reassign from the order page before pickup.',
                'type'          => 'boolean',
                'channel_based' => false,
                'locale_based'  => false,
            ], [
                'name'          => 'auto_schedule_pickup',
                'title'         => 'Book the pickup automatically',
                'info'          => 'Off: book the pickup from the order page once the parcel is packed.',
                'type'          => 'boolean',
                'channel_based' => false,
                'locale_based'  => false,
            ],
        ],
    ], [
        'key'    => 'sales.shiprocket.pickup',
        'name'   => 'Pickup Address',
        'info'   => 'The warehouse address registered in Shiprocket → Settings → Pickup Addresses.',
        'sort'   => 2,
        'fields' => [
            [
                'name'          => 'location',
                'title'         => 'Pickup Location Name',
                'info'          => 'The pickup location nickname exactly as it appears in Shiprocket.',
                'type'          => 'text',
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => false,
            ], [
                'name'          => 'pincode',
                'title'         => 'Pickup Pincode',
                'info'          => 'Used for serviceability and delivery-date checks.',
                'type'          => 'text',
                'validation'    => 'required|digits:6',
                'channel_based' => false,
                'locale_based'  => false,
            ],
        ],
    ], [
        'key'    => 'sales.shiprocket.package',
        'name'   => 'Parcel Defaults',
        'info'   => 'Box size and fallbacks sent with every order.',
        'sort'   => 3,
        'fields' => [
            [
                'name'          => 'length',
                'title'         => 'Box Length (cm)',
                'type'          => 'text',
                'validation'    => 'required|numeric|min:0.5',
                'channel_based' => false,
                'locale_based'  => false,
            ], [
                'name'          => 'breadth',
                'title'         => 'Box Breadth (cm)',
                'type'          => 'text',
                'validation'    => 'required|numeric|min:0.5',
                'channel_based' => false,
                'locale_based'  => false,
            ], [
                'name'          => 'height',
                'title'         => 'Box Height (cm)',
                'type'          => 'text',
                'validation'    => 'required|numeric|min:0.5',
                'channel_based' => false,
                'locale_based'  => false,
            ], [
                'name'          => 'default_item_weight',
                'title'         => 'Default Item Weight (g)',
                'info'          => 'Used when a product has no weight set.',
                'type'          => 'text',
                'validation'    => 'required|numeric|min:1',
                'channel_based' => false,
                'locale_based'  => false,
            ], [
                'name'          => 'hsn',
                'title'         => 'HSN Code',
                'info'          => 'Printed on courier invoices. 6109 = knitted T-shirts.',
                'type'          => 'text',
                'validation'    => 'required|max:8',
                'channel_based' => false,
                'locale_based'  => false,
            ],
        ],
    ],
];

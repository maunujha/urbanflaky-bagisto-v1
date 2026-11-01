<?php

/*
| Admin → Configure → Email → Automated Emails. Merged into Bagisto's `core`
| system config. Unsaved switches fall back to notifications.defaults.
*/

$switch = fn (string $name) => [
    'name'          => $name,
    'title'         => 'notifications::app.system.fields.'.$name,
    'type'          => 'boolean',
    'default'       => true,
    'channel_based' => false,
    'locale_based'  => false,
];

return [
    [
        'key'  => 'emails.automation',
        'name' => 'notifications::app.system.title',
        'info' => 'notifications::app.system.info',
        'icon' => 'settings/store.svg',
        'sort' => 2,
    ], [
        'key'    => 'emails.automation.shipping',
        'name'   => 'notifications::app.system.shipping',
        'info'   => 'notifications::app.system.shipping-info',
        'sort'   => 1,
        'fields' => array_map($switch, ['processing', 'out_for_delivery', 'delivered', 'delivery_failed', 'rto']),
    ], [
        'key'    => 'emails.automation.lifecycle',
        'name'   => 'notifications::app.system.lifecycle',
        'info'   => 'notifications::app.system.lifecycle-info',
        'sort'   => 2,
        'fields' => array_map($switch, ['review_request', 'abandoned_cart', 'win_back']),
    ],
];

<?php

/*
| Admin → Configure → Email → Automated Emails. Merged into Bagisto's `core`
| system config. Unsaved values fall back to notifications.defaults.
*/

$field = fn (string $name, string $type, array $extra = []) => array_merge([
    'name'          => $name,
    'title'         => 'notifications::app.system.fields.'.$name,
    'type'          => $type,
    'channel_based' => false,
    'locale_based'  => false,
], $extra);

$switch = fn (string $group) => fn (string $name) => $field($name, 'boolean', [
    'default' => (bool) config("notifications.defaults.{$group}.{$name}", true),
]);

$days = fn (string $name, int $default) => $field($name, 'text', [
    'validation' => 'required|integer',
    'default'    => $default,
]);

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
        'fields' => array_map($switch('shipping'), ['processing', 'out_for_delivery', 'delivered', 'delivery_failed', 'rto']),
    ], [
        'key'    => 'emails.automation.lifecycle',
        'name'   => 'notifications::app.system.lifecycle',
        'info'   => 'notifications::app.system.lifecycle-info',
        'sort'   => 2,
        'fields' => array_map($switch('lifecycle'), [
            'review_request', 'feedback_request', 'abandoned_cart', 'win_back', 'back_in_stock', 'campaigns',
        ]),
    ], [
        'key'    => 'emails.automation.timing',
        'name'   => 'notifications::app.system.timing',
        'info'   => 'notifications::app.system.timing-info',
        'sort'   => 3,
        'fields' => [
            $days('abandoned_first_hours', 1),
            $days('abandoned_second_hours', 24),
            $days('review_delay_days', 3),
            $days('feedback_delay_days', 7),
            $days('win_back_min_days', 45),
            $days('win_back_max_days', 60),
        ],
    ], [
        'key'    => 'emails.automation.welcome',
        'name'   => 'notifications::app.system.welcome',
        'info'   => 'notifications::app.system.welcome-info',
        'sort'   => 4,
        'fields' => [
            $field('offer_enabled', 'boolean', ['default' => false]),
            $field('offer_coupon', 'text'),
            $field('offer_text', 'text'),
            $field('double_opt_in', 'boolean', ['default' => false]),
        ],
    ],
];

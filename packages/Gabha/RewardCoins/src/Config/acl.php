<?php

/*
| Admin ACL entries. The top-level key must match the menu key so non-super
| roles that are granted `reward-coins` can see the sidebar item.
*/

return [
    [
        'key'   => 'reward-coins',
        'name'  => 'reward-coins::reward_coins.admin.menu.title',
        'route' => 'admin.reward_coins.index',
        'sort'  => 6,
    ], [
        'key'   => 'reward-coins.settings',
        'name'  => 'reward-coins::reward_coins.admin.settings.title',
        'route' => 'admin.reward_coins.settings',
        'sort'  => 1,
    ], [
        'key'   => 'reward-coins.customer',
        'name'  => 'reward-coins::reward_coins.admin.customer.title',
        'route' => 'admin.reward_coins.customer',
        'sort'  => 2,
    ],
];

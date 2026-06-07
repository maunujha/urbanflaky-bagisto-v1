<?php

/*
| "My Coins" entry in the storefront customer-account navigation. Nested under
| the existing "account" group via the `account.*` key convention.
*/

return [
    [
        'key'   => 'account.coins',
        'name'  => 'reward-coins::reward_coins.account.title',
        'route' => 'shop.customers.account.coins.index',
        'icon'  => 'icon-star',
        'sort'  => 4,
    ],
];

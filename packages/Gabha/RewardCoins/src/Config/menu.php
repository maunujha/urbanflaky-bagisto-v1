<?php

/*
| Top-level admin menu entry for the coin program. Kept childless so Bagisto's
| menu builder does not repoint it (see the FAQ package notes on parent/child
| repointing). The dashboard links onward to Settings.
*/

return [
    [
        'key'   => 'reward-coins',
        'name'  => 'reward-coins::reward_coins.admin.menu.title',
        'route' => 'admin.reward_coins.index',
        'sort'  => 6,
        'icon'  => 'icon-promotions',
    ],
];

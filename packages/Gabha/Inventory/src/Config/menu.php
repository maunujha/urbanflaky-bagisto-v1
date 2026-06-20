<?php

return [
    /**
     * Inventory — a new top-level sidebar section with a "Vendors" child.
     *
     * The parent "inventory" item is the section header; Bagisto's menu builder
     * repoints it to its first authorised child route automatically. The child
     * key "inventory.vendors" mirrors the ACL key so role-based visibility works.
     */
    [
        'key'   => 'inventory',
        'name'  => 'inventory::app.menu.inventory',
        'route' => 'admin.inventory.vendors.index',
        'sort'  => 4,
        'icon'  => 'icon-store',
    ], [
        'key'   => 'inventory.vendors',
        'name'  => 'inventory::app.menu.vendors',
        'route' => 'admin.inventory.vendors.index',
        'sort'  => 1,
        'icon'  => '',
    ], [
        'key'   => 'inventory.purchases',
        'name'  => 'inventory::app.menu.purchases',
        'route' => 'admin.inventory.purchases.index',
        'sort'  => 2,
        'icon'  => '',
    ], [
        'key'   => 'inventory.stock',
        'name'  => 'inventory::app.menu.stock',
        'route' => 'admin.inventory.stock.index',
        'sort'  => 3,
        'icon'  => '',
    ], [
        'key'   => 'inventory.movements',
        'name'  => 'inventory::app.menu.movements',
        'route' => 'admin.inventory.movements.index',
        'sort'  => 4,
        'icon'  => '',
    ],
];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Inventory ACL
    |--------------------------------------------------------------------------
    |
    | A new top-level "Inventory" ACL group with a "Vendors" resource beneath
    | it. The menu builder filters items by bouncer()->hasPermission(menuKey),
    | so each ACL key MUST equal its matching sidebar menu key for non-super-
    | admin roles to see the items.
    |
    */
    [
        'key'   => 'inventory',
        'name'  => 'inventory::app.acl.inventory',
        'route' => 'admin.inventory.vendors.index',
        'sort'  => 1,
    ], [
        'key'   => 'inventory.vendors',
        'name'  => 'inventory::app.acl.vendors',
        'route' => 'admin.inventory.vendors.index',
        'sort'  => 1,
    ], [
        'key'   => 'inventory.vendors.create',
        'name'  => 'inventory::app.acl.create',
        'route' => 'admin.inventory.vendors.create',
        'sort'  => 1,
    ], [
        'key'   => 'inventory.vendors.edit',
        'name'  => 'inventory::app.acl.edit',
        'route' => 'admin.inventory.vendors.edit',
        'sort'  => 2,
    ], [
        'key'   => 'inventory.vendors.delete',
        'name'  => 'inventory::app.acl.delete',
        'route' => 'admin.inventory.vendors.delete',
        'sort'  => 3,
    ], [
        'key'   => 'inventory.purchases',
        'name'  => 'inventory::app.acl.purchases',
        'route' => 'admin.inventory.purchases.index',
        'sort'  => 2,
    ], [
        'key'   => 'inventory.purchases.create',
        'name'  => 'inventory::app.acl.create',
        'route' => 'admin.inventory.purchases.create',
        'sort'  => 1,
    ], [
        'key'   => 'inventory.purchases.view',
        'name'  => 'inventory::app.acl.view',
        'route' => 'admin.inventory.purchases.view',
        'sort'  => 2,
    ], [
        'key'   => 'inventory.purchases.add-items',
        'name'  => 'inventory::app.acl.add-items',
        'route' => 'admin.inventory.purchases.add-items',
        'sort'  => 3,
    ], [
        'key'   => 'inventory.stock',
        'name'  => 'inventory::app.acl.stock',
        'route' => 'admin.inventory.stock.index',
        'sort'  => 3,
    ], [
        'key'   => 'inventory.movements',
        'name'  => 'inventory::app.acl.movements',
        'route' => 'admin.inventory.movements.index',
        'sort'  => 4,
    ], [
        'key'   => 'inventory.movements.create',
        'name'  => 'inventory::app.acl.create',
        'route' => 'admin.inventory.movements.create',
        'sort'  => 1,
    ],
];

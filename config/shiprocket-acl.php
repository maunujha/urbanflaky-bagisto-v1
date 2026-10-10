<?php

/*
| "Manage Shiprocket shipments" permission, under Sales → Orders in the role
| editor. Checked by ShiprocketShipmentController for every action.
*/

return [
    [
        'key'   => 'sales.orders.shiprocket',
        'name'  => 'Manage Shiprocket Shipments',
        'route' => 'admin.sales.orders.shiprocket.push',
        'sort'  => 6,
    ],
];

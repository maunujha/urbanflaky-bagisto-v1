<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Low Stock Threshold
    |--------------------------------------------------------------------------
    |
    | A variant whose current stock is at or below this value is treated as
    | "low stock" — used by the inventory list filter and the dashboard card.
    |
    */
    'low_stock_threshold' => (int) env('INVENTORY_LOW_STOCK_THRESHOLD', 10),
];

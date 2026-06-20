<?php

return [
    'menu' => [
        'inventory' => 'Inventory',
        'vendors'   => 'Vendors',
        'purchases' => 'Purchases',
        'stock'     => 'Inventory',
        'movements' => 'Stock Movements',
    ],

    'acl' => [
        'inventory' => 'Inventory',
        'vendors'   => 'Vendors',
        'purchases' => 'Purchases',
        'stock'     => 'Inventory',
        'movements' => 'Stock Movements',
        'create'    => 'Create',
        'edit'      => 'Edit',
        'delete'    => 'Delete',
        'view'      => 'View',
    ],

    'admin' => [
        'vendors' => [
            'index' => [
                'title'      => 'Vendors',
                'create-btn' => 'Create Vendor',

                'datagrid' => [
                    'id'                 => 'ID',
                    'name'               => 'Vendor Name',
                    'mobile'             => 'Mobile',
                    'address'            => 'Address',
                    'total-purchases'    => 'Total Purchases',
                    'last-purchase-date' => 'Last Purchase',
                    'created-at'         => 'Created At',
                    'edit'               => 'Edit',
                    'delete'             => 'Delete',
                ],
            ],

            'create' => [
                'title'               => 'Create Vendor',
                'save-btn'            => 'Save Vendor',
                'back-btn'            => 'Back',
                'general'             => 'Vendor Details',
                'name'                => 'Vendor Name',
                'name-placeholder'    => 'e.g. Sharma Textiles',
                'mobile'              => 'Mobile',
                'mobile-placeholder'  => 'e.g. 9876543210',
                'address'             => 'Address',
                'address-placeholder' => 'Full address of the vendor',
            ],

            'edit' => [
                'title'    => 'Edit Vendor',
                'save-btn' => 'Save Vendor',
            ],

            'create-success'       => 'Vendor created successfully.',
            'update-success'       => 'Vendor updated successfully.',
            'delete-success'       => 'Vendor deleted successfully.',
            'delete-failed'        => 'Vendor could not be deleted.',
            'delete-has-purchases' => 'This vendor cannot be deleted because it has purchase records.',
            'mass-delete-success'  => 'Selected vendors deleted successfully.',
            'mass-delete-partial'  => ':blocked vendor(s) with purchase records were skipped; the rest were deleted.',
        ],

        'purchases' => [
            'index' => [
                'title'      => 'Purchases',
                'create-btn' => 'Create Purchase',

                'datagrid' => [
                    'id'              => 'ID',
                    'purchase-number' => 'Purchase #',
                    'vendor'          => 'Vendor',
                    'purchase-date'   => 'Purchase Date',
                    'invoice-number'  => 'Invoice #',
                    'total-quantity'  => 'Total Qty',
                    'total-amount'    => 'Total Amount',
                    'created-at'      => 'Created At',
                    'view'            => 'View',
                ],
            ],

            'create' => [
                'title'                  => 'Create Purchase',
                'save-btn'               => 'Save Purchase',
                'back-btn'               => 'Back',

                'step-vendor'            => 'Step 1: Select Vendor',
                'step-info'              => 'Step 2: Purchase Information',
                'step-products'          => 'Step 3: Add Products',

                'vendor'                 => 'Vendor',
                'vendor-placeholder'     => 'Select a vendor',
                'no-vendors'             => 'No vendors found. Please create a vendor first.',
                'create-vendor'          => 'Create Vendor',

                'purchase-date'          => 'Purchase Date',
                'invoice-number'         => 'Invoice Number',
                'invoice-number-placeholder' => 'Vendor invoice / bill number',
                'bill'                   => 'Bill Upload',
                'bill-info'              => 'Accepted: PDF, JPG, JPEG, PNG. Max 4 MB.',
                'notes'                  => 'Notes',

                'products'               => 'Products',
                'product-variant'        => 'Product Variant',
                'search-placeholder'     => 'Search product variant by name or SKU…',
                'searching'              => 'Searching…',
                'no-results'             => 'No matching variants found.',
                'quantity'               => 'Quantity',
                'unit-cost'              => 'Cost Per Unit',
                'line-total'             => 'Line Total',
                'remove'                 => 'Remove',
                'sku'                    => 'SKU: :sku',
                'no-products'            => 'No products added yet. Use the search above to add product variants.',
                'total-quantity'         => 'Total Quantity',
                'grand-total'            => 'Grand Total',
                'already-added'          => 'This variant has already been added.',

                'items-required'         => 'Add at least one product to the purchase.',
                'items-distinct'         => 'Each product variant may only be added once.',
            ],

            'view' => [
                'title'           => 'Purchase :number',
                'back-btn'        => 'Back',
                'vendor'          => 'Vendor',
                'purchase-date'   => 'Purchase Date',
                'invoice-number'  => 'Invoice Number',
                'bill'            => 'Bill',
                'download-bill'   => 'Download Bill',
                'no-bill'         => 'No bill uploaded',
                'notes'           => 'Notes',
                'items'           => 'Items',
                'product-variant' => 'Product Variant',
                'quantity'        => 'Quantity',
                'unit-cost'       => 'Unit Cost',
                'line-total'      => 'Line Total',
                'total-quantity'  => 'Total Quantity',
                'grand-total'     => 'Grand Total',
                'deleted-variant' => 'Variant removed from catalog',
            ],

            'create-success' => 'Purchase :number created and stock updated successfully.',
        ],

        'stock' => [
            'index' => [
                'title' => 'Inventory',

                'cards' => [
                    'total-units'   => 'Total Inventory Units',
                    'total-value'   => 'Total Inventory Value',
                    'low-stock'     => 'Low Stock Products',
                    'total-vendors' => 'Total Vendors',
                    'units'         => 'Units',
                    'products'      => 'Products',
                    'vendors'       => 'Vendors',
                    'low-stock-hint' => 'At or below :threshold units',
                ],

                'datagrid' => [
                    'product'         => 'Product',
                    'sku'             => 'SKU',
                    'color'           => 'Color',
                    'size'            => 'Size',
                    'current-stock'   => 'Current Stock',
                    'average-cost'    => 'Average Cost',
                    'inventory-value' => 'Inventory Value',
                    'low-stock'       => 'Stock Status',
                    'low'             => 'Low Stock',
                    'in-stock'        => 'In Stock',
                ],
            ],
        ],

        'movements' => [
            'index' => [
                'title'      => 'Stock Movements',
                'create-btn' => 'Add Stock Movement',

                'datagrid' => [
                    'date'             => 'Date',
                    'movement-number'  => 'Movement #',
                    'product'          => 'Product',
                    'sku'              => 'SKU',
                    'movement-type'    => 'Type',
                    'quantity'         => 'Quantity',
                    'previous-stock'   => 'Previous Stock',
                    'new-stock'        => 'New Stock',
                    'reference-number' => 'Reference #',
                    'notes'            => 'Notes',
                    'deleted-variant'  => 'Variant removed from catalog',
                ],
            ],

            'create' => [
                'title'              => 'Add Stock Movement',
                'save-btn'           => 'Save Movement',
                'back-btn'           => 'Back',
                'general'            => 'Movement Details',
                'product-variant'    => 'Product Variant',
                'search-placeholder' => 'Search product variant by name or SKU…',
                'searching'          => 'Searching…',
                'no-results'         => 'No matching variants found.',
                'selected-variant'   => 'Selected Variant',
                'current-stock'      => 'Current Stock',
                'sku'                => 'SKU',
                'change-variant'     => 'Change',
                'movement-type'      => 'Movement Type',
                'movement-type-placeholder' => 'Select movement type',
                'quantity'           => 'Quantity',
                'notes'              => 'Notes',
                'select-variant-first' => 'Please select a product variant.',
            ],

            'create-success' => 'Stock movement :number recorded and inventory updated.',
            'negative-stock' => 'Insufficient stock: only :available unit(s) on hand, cannot remove :requested.',
        ],
    ],
];

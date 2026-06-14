<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-looking scalability indexes.
 *
 * The store is small today, so none of these change current latency. They exist
 * to keep JOIN/WHERE columns off full-table scans once orders, invoices and
 * shipments accumulate. Every entry maps to a real Bagisto query pattern
 * (sales reports, order detail views, customer history, checkout, cart load).
 *
 * Each index is added defensively (table + column + index existence checks) so
 * the migration is idempotent and safe across Bagisto upgrades.
 */
return new class extends Migration
{
    /**
     * [table, column(s), index name].
     */
    protected array $indexes = [
        // --- Sales reporting: grouping/joining order lines by product ---
        ['order_items',           'product_id',       'oi_product_id_idx'],
        ['invoice_items',         'product_id',       'ii_product_id_idx'],
        ['invoice_items',         'order_item_id',    'ii_order_item_id_idx'],
        ['shipment_items',        'product_id',       'si_product_id_idx'],
        ['shipment_items',        'order_item_id',    'si_order_item_id_idx'],
        ['refund_items',          'product_id',       'ri_product_id_idx'],

        // --- Customer-facing joins ---
        ['product_reviews',       'customer_id',      'prod_rev_customer_id_idx'],
        ['shipments',             'customer_id',      'shp_customer_id_idx'],
        ['shipments',             'order_address_id', 'shp_order_address_id_idx'],

        // --- Catalog navigation: category tree traversal ---
        ['categories',            'parent_id',        'cat_parent_id_idx'],

        // --- Checkout / payment reconciliation ---
        ['orders',                'cart_id',          'ord_cart_id_idx'],
        ['order_transactions',    'invoice_id',       'ot_invoice_id_idx'],

        // --- Cart load (transient but joined on every cart read) ---
        ['cart_item_inventories', 'cart_item_id',     'cii_cart_item_id_idx'],
        ['cart_shipping_rates',   'cart_address_id',  'csr_cart_address_id_idx'],
    ];

    public function up(): void
    {
        foreach ($this->indexes as [$table, $column, $name]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            if (Schema::hasIndex($table, $name)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($column, $name) {
                $t->index($column, $name);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as [$table, $column, $name]) {
            if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $name)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($name) {
                $t->dropIndex($name);
            });
        }
    }
};

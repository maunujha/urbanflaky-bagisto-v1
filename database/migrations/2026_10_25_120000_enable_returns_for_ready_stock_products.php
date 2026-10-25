<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Apply the store's 7-day return policy to the catalogue.
 *
 * Every ready-stock product gets allow_rma = 1 so the RMA flow accepts returns
 * and the Product schema can declare the window; Made-on-Demand products are
 * customised and non-returnable per /return-policy, so they get allow_rma = 0.
 * Variants follow their parent's fulfilment type. The attribute default becomes
 * 1 so new products start returnable in the admin form.
 */
return new class extends Migration
{
    public function up(): void
    {
        $attributeId = DB::table('attributes')->where('code', 'allow_rma')->value('id');

        if (! $attributeId) {
            return;
        }

        $channels = DB::table('channels')->pluck('code');

        $products = DB::table('products as p')
            ->leftJoin('products as parent', 'parent.id', '=', 'p.parent_id')
            ->select('p.id', DB::raw('COALESCE(parent.is_made_on_demand, p.is_made_on_demand) as made_on_demand'))
            ->get();

        foreach ($products as $product) {
            foreach ($channels as $channel) {
                DB::table('product_attribute_values')->updateOrInsert(
                    ['unique_id' => implode('|', [$channel, $product->id, $attributeId])],
                    [
                        'product_id'    => $product->id,
                        'attribute_id'  => $attributeId,
                        'channel'       => $channel,
                        'locale'        => null,
                        'boolean_value' => $product->made_on_demand ? 0 : 1,
                    ]
                );
            }
        }

        DB::table('attributes')->where('id', $attributeId)->update(['default_value' => 1]);
    }

    /**
     * Restores the attribute default only; per-product return settings are kept.
     */
    public function down(): void
    {
        DB::table('attributes')->where('code', 'allow_rma')->update(['default_value' => 0]);
    }
};

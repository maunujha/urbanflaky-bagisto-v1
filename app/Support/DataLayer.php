<?php

namespace App\Support;

/**
 * Builds GA4-shaped ecommerce payloads for the GTM data layer.
 *
 * Single source of truth for server-rendered events (view_item, view_item_list,
 * purchase). Client-side events (add_to_cart, begin_checkout, …) reuse the same
 * item shape by consuming arrays produced here and injected via @json.
 *
 * Conventions (documented in docs/analytics/EVENTS.md):
 *   - item_id   = product SKU (must match the Google Merchant Center feed id).
 *   - item_brand = "Urbanflaky" (single-brand store).
 *   - currency  = active channel currency code (INR).
 *   - purchase value = order grand total (what the customer paid); tax & shipping
 *     are reported separately as GA4 expects.
 */
class DataLayer
{
    public const BRAND = 'Urbanflaky';

    /**
     * Active currency code, e.g. "INR".
     */
    public static function currency(): string
    {
        return core()->getCurrentCurrencyCode();
    }

    /**
     * Queue a data-layer event to fire on the NEXT page render.
     *
     * Used for redirect-based actions (contact form, login, sign-up) where the
     * outcome is only known server-side. Events accumulate within a request and
     * are flushed (and auto-cleared) by the tracking head partial.
     */
    public static function flash(array $event): void
    {
        $events = session()->get('datalayer_events', []);

        $events[] = $event;

        session()->flash('datalayer_events', $events);
    }

    /**
     * GA4 item array for a catalog product model.
     */
    public static function productItem($product, array $overrides = []): array
    {
        $price = (float) $product->getTypeInstance()->getMinimalPrice();

        if ($price <= 0) {
            $price = (float) $product->price;
        }

        return array_merge([
            'item_id'       => $product->sku,
            'item_name'     => $product->name,
            'item_brand'    => self::BRAND,
            'item_category' => optional($product->categories->first())->name,
            'price'         => round($price, 2),
            'quantity'      => 1,
        ], $overrides);
    }

    /**
     * Full `ecommerce` object for a view_item event.
     */
    public static function viewItem($product): array
    {
        $item = self::productItem($product);

        return [
            'currency' => self::currency(),
            'value'    => $item['price'],
            'items'    => [$item],
        ];
    }

    /**
     * GA4 item array for a placed-order line item.
     *
     * Pulls the chosen variant (e.g. size) out of the item's `additional`
     * payload so item_variant is populated for configurable products.
     */
    public static function orderItem($item, int $index = 0): array
    {
        $variant = null;

        $attributes = $item->additional['attributes'] ?? [];

        if (is_array($attributes)) {
            $variant = collect($attributes)->pluck('option_label')->filter()->implode(' / ') ?: null;
        }

        return array_filter([
            'item_id'       => $item->sku,
            'item_name'     => $item->name,
            'item_brand'    => self::BRAND,
            'item_variant'  => $variant,
            'price'         => round((float) $item->price, 2),
            'quantity'      => (int) $item->qty_ordered,
            'index'         => $index,
        ], fn ($v) => $v !== null);
    }

    /**
     * Full `ecommerce` object for a purchase event built from an Order model.
     */
    public static function purchase($order): array
    {
        $items = [];

        foreach ($order->items as $i => $item) {
            // One row per ordered product; skip child rows of configurables to
            // avoid double-counting (parent row carries the saleable line).
            if ($item->parent_id !== null) {
                continue;
            }

            $items[] = self::orderItem($item, $i);
        }

        return array_filter([
            'transaction_id' => $order->increment_id,
            'order_id'       => $order->id,
            'value'          => round((float) $order->grand_total, 2),
            'tax'            => round((float) $order->tax_amount, 2),
            'shipping'       => round((float) $order->shipping_amount, 2),
            'currency'       => $order->order_currency_code ?? self::currency(),
            'coupon'         => $order->coupon_code,
            'discount'       => round((float) $order->discount_amount, 2),
            'payment_method' => optional($order->payment)->method_title ?? optional($order->payment)->method,
            'items'          => $items,
        ], fn ($v) => $v !== null && $v !== '');
    }
}

{{--
    Product lines with picture, chosen options, quantity, unit price and line
    total. Takes order, invoice, shipment, refund or cart items; child rows of
    configurable products are skipped (their options sit on the parent row).
--}}
@props(['items', 'currency' => null, 'prices' => true])

@php
    $rows = collect($items)
        ->filter(fn ($item) => empty($item->parent_id))
        ->map(function ($item) {
            $attributes = $item->additional['attributes'] ?? [];

            $options = collect(is_array($attributes) ? $attributes : [])
                ->reject(fn ($attribute) => ($attribute['attribute_type'] ?? null) === 'file')
                ->pluck('option_label')
                ->filter()
                ->implode(' / ');

            $product = $item->product;
            $source = $product?->images?->count() ? $product : $product?->parent;
            $image = $source?->images?->count()
                ? (product_image()->getProductBaseImage($source)['small_image_url'] ?? null)
                : null;

            $qty = (int) ($item->qty_ordered ?? $item->qty ?? $item->quantity ?? 1);
            $unit = $item->price_incl_tax ?? $item->price ?? 0;
            $total = $item->total_incl_tax ?? $item->total ?? ($unit * $qty);

            return compact('item', 'options', 'image', 'qty', 'unit', 'total');
        });
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px 0;">
    @foreach ($rows as $row)
        <tr>
            <td width="76" valign="top" style="padding:16px 14px 16px 0; border-top:1px solid #262626;">
                @if ($row['image'])
                    <img src="{{ $row['image'] }}" alt="{{ $row['item']->name }}" width="64" style="display:block; width:64px; height:auto; border-radius:4px; background-color:#1c1c1c;" />
                @else
                    <div style="width:64px; height:80px; border-radius:4px; background-color:#1c1c1c;">&nbsp;</div>
                @endif
            </td>
            <td valign="top" style="padding:16px 0; border-top:1px solid #262626;">
                <div style="font-size:15px; line-height:21px; font-weight:600; color:#f5f5f5;">{{ $row['item']->name }}</div>

                @if ($row['options'])
                    <div style="margin-top:3px; font-size:13px; line-height:19px; color:#a3a3a3;">{{ $row['options'] }}</div>
                @endif

                <div style="margin-top:3px; font-size:13px; line-height:19px; color:#a3a3a3;">
                    @if ($prices)
                        @lang('shop::app.emails.qty-price', ['qty' => $row['qty'], 'price' => core()->formatPrice($row['unit'], $currency)])
                    @else
                        @lang('shop::app.emails.qty', ['qty' => $row['qty']])
                    @endif
                </div>
            </td>

            @if ($prices)
                <td align="right" valign="top" style="padding:16px 0 16px 12px; border-top:1px solid #262626; font-size:15px; line-height:21px; font-weight:600; color:#f5f5f5; white-space:nowrap;">
                    {{ core()->formatPrice($row['total'], $currency) }}
                </td>
            @endif
        </tr>
    @endforeach
</table>

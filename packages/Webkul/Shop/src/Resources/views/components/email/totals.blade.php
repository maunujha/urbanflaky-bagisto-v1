{{--
    Money summary for an order, invoice or refund (all carry the same total
    columns). Prices include GST (inclusive pricing), so tax is shown as the
    part already included, never added on top.
--}}
@props(['source', 'currency', 'grandLabel' => null])

@php
    $rows = [
        [trans('shop::app.emails.orders.subtotal'), $source->sub_total_incl_tax ?? $source->sub_total],
    ];

    if ((float) $source->discount_amount > 0) {
        $rows[] = [trans('shop::app.emails.orders.discount'), -1 * (float) $source->discount_amount];
    }

    $shipping = (float) ($source->shipping_amount_incl_tax ?? $source->shipping_amount ?? 0);
    $rows[] = [trans('shop::app.emails.orders.shipping-handling'), $shipping, $shipping > 0 ? null : trans('shop::app.emails.free')];
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 28px 0;">
    @foreach ($rows as $row)
        <tr>
            <td style="padding:5px 0; font-size:14px; line-height:20px; color:#a3a3a3;">{{ $row[0] }}</td>
            <td align="right" style="padding:5px 0; font-size:14px; line-height:20px; color:#f5f5f5; white-space:nowrap;">
                {{ $row[2] ?? core()->formatPrice($row[1], $currency) }}
            </td>
        </tr>
    @endforeach

    <tr>
        <td style="padding:14px 0 4px 0; border-top:1px solid #f5f5f5; font-size:15px; line-height:20px; font-weight:700; color:#f5f5f5; text-transform:uppercase; letter-spacing:1px;">
            {{ $grandLabel ?? trans('shop::app.emails.orders.grand-total') }}
        </td>
        <td align="right" style="padding:14px 0 4px 0; border-top:1px solid #f5f5f5; font-size:18px; line-height:22px; font-weight:700; color:#f5f5f5; white-space:nowrap;">
            {{ core()->formatPrice($source->grand_total, $currency) }}
        </td>
    </tr>

    @if ((float) $source->tax_amount > 0)
        <tr>
            <td colspan="2" align="right" style="padding:2px 0 0 0; font-size:12px; line-height:18px; color:#a3a3a3;">
                @lang('shop::app.emails.tax-included', ['amount' => core()->formatPrice($source->tax_amount, $currency)])
            </td>
        </tr>
    @endif
</table>

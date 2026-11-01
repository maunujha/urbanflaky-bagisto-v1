@component('shop::emails.layout')
    @include('notifications::emails.partials.heading', [
        'title' => trans($copy.'.title'),
        'name'  => $cart->customer_first_name,
    ])

    <p style="margin:0 0 24px 0; font-size:16px; color:#384860; line-height:24px;">
        @lang($copy.'.body')
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        @foreach ($items as $item)
            <tr>
                <td style="padding:14px 0; border-top:1px solid #ececec;">
                    <div style="font-size:15px; color:#0a0a0a; font-weight:600;">{{ $item->name }}</div>
                    <div style="margin-top:4px; font-size:13px; color:#6b7280;">
                        @lang('notifications::app.emails.cart.qty', ['qty' => $item->quantity])
                    </div>
                </td>
                <td align="right" style="padding:14px 0; border-top:1px solid #ececec; font-size:15px; color:#0a0a0a; white-space:nowrap;">
                    {{ core()->formatPrice($item->total_incl_tax ?: $item->total, $cart->cart_currency_code) }}
                </td>
            </tr>
        @endforeach
    </table>

    @include('notifications::emails.partials.button', [
        'url'   => $recoverUrl,
        'label' => trans('notifications::app.emails.cart.cta'),
    ])

    @include('notifications::emails.partials.unsubscribe', [
        'reason' => trans('notifications::app.emails.why-marketing'),
    ])
@endcomponent

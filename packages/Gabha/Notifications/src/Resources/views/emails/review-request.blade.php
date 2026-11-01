@component('shop::emails.layout', [
    'preheader'         => trans('notifications::app.emails.review.body'),
    'unsubscribeUrl'    => $unsubscribeUrl,
    'unsubscribeReason' => trans('notifications::app.emails.review.why'),
    'unsubscribeLabel'  => trans('notifications::app.emails.review.opt-out'),
])
    <x-shop::email.heading
        :context="trans('notifications::app.emails.order-ref', ['order_id' => $order->increment_id])"
        :title="trans('notifications::app.emails.review.title')"
        :name="$order->customer_first_name"
    />

    <x-shop::email.text>
        @lang('notifications::app.emails.review.body')
    </x-shop::email.text>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px 0;">
        @foreach ($products as $product)
            <tr>
                @if ($product['image'])
                    <td width="76" valign="middle" style="padding:14px 14px 14px 0; border-top:1px solid #262626;">
                        <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" width="64" style="display:block; width:64px; height:auto; border-radius:4px; background-color:#1c1c1c;" />
                    </td>
                @endif
                <td valign="middle" style="padding:14px 0; border-top:1px solid #262626; font-size:15px; line-height:21px; font-weight:600; color:#f5f5f5;">
                    {{ $product['name'] }}
                </td>
                <td align="right" valign="middle" style="padding:14px 0 14px 12px; border-top:1px solid #262626;">
                    <a href="{{ $product['url'] }}" target="_blank" style="display:inline-block; padding:9px 16px; border:1px solid #f0f0f0; border-radius:2px; font-size:12px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:#f0f0f0; text-decoration:none; white-space:nowrap;">
                        @lang('notifications::app.emails.review.cta')
                    </a>
                </td>
            </tr>
        @endforeach
    </table>
@endcomponent

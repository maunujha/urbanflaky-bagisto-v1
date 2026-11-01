@component('shop::emails.layout', [
    'preheader'      => trans($copy.'.body'),
    'unsubscribeUrl' => $unsubscribeUrl,
])
    <x-shop::email.heading
        :title="trans($copy.'.title')"
        :name="$cart->customer_first_name"
    />

    <x-shop::email.text>
        @lang($copy.'.body')
    </x-shop::email.text>

    <x-shop::email.button :url="$recoverUrl">
        @lang('notifications::app.emails.cart.cta')
    </x-shop::email.button>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px 0;">
        @foreach ($items as $item)
            <tr>
                <td width="76" valign="top" style="padding:16px 14px 16px 0; border-top:1px solid #262626;">
                    @if ($item['image'])
                        <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" width="64" style="display:block; width:64px; height:auto; border-radius:4px; background-color:#1c1c1c;" />
                    @else
                        <div style="width:64px; height:80px; border-radius:4px; background-color:#1c1c1c;">&nbsp;</div>
                    @endif
                </td>
                <td valign="top" style="padding:16px 0; border-top:1px solid #262626;">
                    <div style="font-size:15px; line-height:21px; font-weight:600; color:#f5f5f5;">{{ $item['name'] }}</div>
                    @if ($item['options'])
                        <div style="margin-top:3px; font-size:13px; line-height:19px; color:#a3a3a3;">{{ $item['options'] }}</div>
                    @endif
                    <div style="margin-top:3px; font-size:13px; line-height:19px; color:#a3a3a3;">
                        @lang('notifications::app.emails.cart.qty-price', ['qty' => $item['qty'], 'price' => $item['price']])
                    </div>
                </td>
                <td align="right" valign="top" style="padding:16px 0 16px 12px; border-top:1px solid #262626; font-size:15px; line-height:21px; font-weight:600; color:#f5f5f5; white-space:nowrap;">
                    {{ $item['total'] }}
                </td>
            </tr>
        @endforeach

        <tr>
            <td colspan="2" style="padding:14px 0 4px 0; border-top:1px solid #f5f5f5; font-size:15px; line-height:20px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:#f5f5f5;">
                @lang('notifications::app.emails.cart.total')
            </td>
            <td align="right" style="padding:14px 0 4px 0; border-top:1px solid #f5f5f5; font-size:18px; line-height:22px; font-weight:700; color:#f5f5f5; white-space:nowrap;">
                {{ $total }}
            </td>
        </tr>
    </table>
@endcomponent

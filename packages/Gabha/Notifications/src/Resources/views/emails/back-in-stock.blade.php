@component('shop::emails.layout', ['preheader' => trans('notifications::app.emails.back-in-stock.subject', ['product' => $name])])
    <x-shop::email.heading :title="trans('notifications::app.emails.back-in-stock.title')" />

    <x-shop::email.text>
        @lang('notifications::app.emails.back-in-stock.body')
    </x-shop::email.text>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px 0;">
        <tr>
            @if ($image)
                <td width="132" valign="top" style="padding:18px 18px 18px 0; border-top:1px solid #262626; border-bottom:1px solid #262626;">
                    <img src="{{ $image }}" alt="{{ $name }}" width="114" style="display:block; width:114px; height:auto; border-radius:4px; background-color:#1c1c1c;" />
                </td>
            @endif
            <td valign="middle" style="padding:18px 0; border-top:1px solid #262626; border-bottom:1px solid #262626;">
                <div style="font-size:17px; line-height:24px; font-weight:600; color:#f5f5f5;">{{ $name }}</div>
                @if ($options)
                    <div style="margin-top:4px; font-size:14px; line-height:20px; color:#a3a3a3;">{{ $options }}</div>
                @endif
                <div style="margin-top:10px; font-size:16px; line-height:22px; font-weight:600; color:#f5f5f5;">{{ $price }}</div>
            </td>
        </tr>
    </table>

    <x-shop::email.button :url="$url">
        @lang('notifications::app.emails.back-in-stock.cta')
    </x-shop::email.button>

    <x-shop::email.text muted>
        @lang('notifications::app.emails.back-in-stock.why')
    </x-shop::email.text>
@endcomponent

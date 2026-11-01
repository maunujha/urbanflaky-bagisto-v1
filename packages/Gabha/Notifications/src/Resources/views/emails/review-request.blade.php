@component('shop::emails.layout')
    @include('notifications::emails.partials.heading', [
        'title' => trans('notifications::app.emails.review.title'),
        'name'  => $order->customer_first_name,
    ])

    <p style="margin:0 0 24px 0; font-size:16px; color:#384860; line-height:24px;">
        @lang('notifications::app.emails.review.body')
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;">
        @foreach ($products as $product)
            <tr>
                <td style="padding:14px 0; border-top:1px solid #ececec; font-size:15px; color:#0a0a0a; font-weight:600;">
                    {{ $product['name'] }}
                </td>
                <td align="right" style="padding:14px 0; border-top:1px solid #ececec;">
                    <a href="{{ $product['url'] }}" target="_blank" style="font-size:13px; font-weight:600; letter-spacing:1px; color:#0a0a0a; text-transform:uppercase; text-decoration:underline;">
                        @lang('notifications::app.emails.review.cta')
                    </a>
                </td>
            </tr>
        @endforeach
    </table>

    @include('notifications::emails.partials.unsubscribe', [
        'label' => trans('notifications::app.emails.review.opt-out'),
    ])
@endcomponent

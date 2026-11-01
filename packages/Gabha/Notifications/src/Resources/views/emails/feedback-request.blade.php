@component('shop::emails.layout', [
    'preheader'         => trans('notifications::app.emails.feedback.body', ['order_id' => $order->increment_id]),
    'unsubscribeUrl'    => $unsubscribeUrl,
    'unsubscribeReason' => trans('notifications::app.emails.review.why'),
    'unsubscribeLabel'  => trans('notifications::app.emails.review.opt-out'),
])
    <x-shop::email.heading
        :context="trans('notifications::app.emails.order-ref', ['order_id' => $order->increment_id])"
        :title="trans('notifications::app.emails.feedback.title')"
        :name="$order->customer_first_name"
    />

    <x-shop::email.text>
        @lang('notifications::app.emails.feedback.body', ['order_id' => $order->increment_id])
    </x-shop::email.text>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:6px 0 10px 0;">
        <tr>
            @foreach ($ratings as $rating => $url)
                <td style="padding-right:8px;">
                    <a
                        href="{{ $url }}"
                        target="_blank"
                        aria-label="@lang('notifications::app.emails.feedback.rate', ['rating' => $rating])"
                        style="display:inline-block; width:46px; height:46px; line-height:46px; text-align:center; border:1px solid {{ $rating === 5 ? '#c7eb31' : '#525252' }}; border-radius:2px; font-size:16px; font-weight:700; color:#f5f5f5; text-decoration:none;"
                    >{{ $rating }}</a>
                </td>
            @endforeach
        </tr>
    </table>

    <x-shop::email.text muted gap="28px">
        @lang('notifications::app.emails.feedback.scale')
    </x-shop::email.text>
@endcomponent

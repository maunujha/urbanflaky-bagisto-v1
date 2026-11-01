@component('shop::emails.layout')
    <p style="margin:0 0 8px 0; font-size:12px; font-weight:600; letter-spacing:2px; color:#6b7280; text-transform:uppercase;">
        @lang('notifications::app.emails.order-ref', ['order_id' => $order->increment_id])
    </p>

    @include('notifications::emails.partials.heading', [
        'title' => trans($copy.'.title'),
        'name'  => $order->customer_first_name,
    ])

    <p style="margin:0; font-size:16px; color:#384860; line-height:24px;">
        @lang($copy.'.body')
    </p>

    @if ($stage === 'out_for_delivery' && $order->payment?->method === 'cashondelivery')
        <p style="margin:16px 0 0 0; font-size:16px; color:#0a0a0a; font-weight:600; line-height:24px;">
            @lang($copy.'.cod', ['amount' => core()->formatPrice($order->grand_total, $order->order_currency_code)])
        </p>
    @endif

    @include('notifications::emails.partials.button', [
        'url'   => $trackUrl ?? $orderUrl,
        'label' => trans($trackUrl ? 'notifications::app.emails.shipment.track' : 'notifications::app.emails.shipment.view'),
    ])
@endcomponent

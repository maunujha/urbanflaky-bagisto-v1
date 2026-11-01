@lang('notifications::app.emails.order-ref', ['order_id' => $order->increment_id])

@lang($copy.'.title')

@lang('notifications::app.emails.hello', ['name' => $order->customer_first_name])

@lang($copy.'.body')
@if ($stage === 'out_for_delivery' && $order->payment?->method === 'cashondelivery')

@lang($copy.'.cod', ['amount' => core()->formatPrice($order->grand_total, $order->order_currency_code)])
@endif

{{ trans($trackUrl ? 'notifications::app.emails.shipment.track' : 'notifications::app.emails.shipment.view') }}: {{ $trackUrl ?? $orderUrl }}

@include('notifications::emails.partials.text-footer')

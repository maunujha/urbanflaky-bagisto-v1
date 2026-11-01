@component('shop::emails.layout', ['preheader' => trans($copy.'.subject', ['order_id' => $order->increment_id])])
    <x-shop::email.heading
        :context="trans('notifications::app.emails.order-ref', ['order_id' => $order->increment_id])"
        :title="trans($copy.'.title')"
        :name="$order->customer_first_name"
    />

    <x-shop::email.text>
        @lang($copy.'.body')
    </x-shop::email.text>

    @if ($stage === 'out_for_delivery' && $order->payment?->method === 'cashondelivery')
        <x-shop::email.panel accent>
            <strong>@lang($copy.'.cod', ['amount' => core()->formatPrice($order->grand_total, $order->order_currency_code)])</strong>
        </x-shop::email.panel>
    @endif

    <x-shop::email.button :url="$trackUrl ?? $orderUrl">
        {{ trans($trackUrl ? 'notifications::app.emails.shipment.track' : 'notifications::app.emails.shipment.view') }}
    </x-shop::email.button>

    <x-shop::email.items :items="$order->items" :currency="$order->order_currency_code" :prices="false" />
@endcomponent

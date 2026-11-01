{{-- Refund issued: what was refunded and the amount. --}}
@php($order = $refund->order)

@component('shop::emails.layout', ['preheader' => trans('shop::app.emails.orders.refunded.preheader', ['amount' => core()->formatPrice($refund->grand_total, $order->order_currency_code)])])
    <x-shop::email.heading
        :context="trans('shop::app.emails.order-ref', ['order_id' => $order->increment_id])"
        :title="trans('shop::app.emails.orders.refunded.title')"
        :name="$order->customer_first_name"
    />

    <x-shop::email.panel accent>
        <strong>@lang('shop::app.emails.orders.refunded.amount', ['amount' => core()->formatPrice($refund->grand_total, $order->order_currency_code)])</strong><br />
        <span style="color:#a3a3a3;">@lang('shop::app.emails.orders.refunded.timing')</span>
    </x-shop::email.panel>

    <x-shop::email.items :items="$refund->items" :currency="$order->order_currency_code" />

    <x-shop::email.totals :source="$refund" :currency="$order->order_currency_code" :grand-label="trans('shop::app.emails.orders.refunded.total')" />

    <x-shop::email.button :url="route('shop.customers.account.orders.view', $order->id)">
        @lang('shop::app.emails.orders.view-order')
    </x-shop::email.button>
@endcomponent

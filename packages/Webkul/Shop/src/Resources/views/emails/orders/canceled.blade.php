{{-- Order cancelled. Refund wording only for orders that were actually paid online. --}}
@php
    $paidOnline = $order->transactions()->where('status', 'captured')->exists()
        && ! in_array($order->payment?->method, config('notifications.offline_payment_methods', ['cashondelivery', 'moneytransfer']), true);
@endphp

@component('shop::emails.layout', ['preheader' => trans('shop::app.emails.orders.canceled.preheader', ['order_id' => $order->increment_id])])
    <x-shop::email.heading
        :context="trans('shop::app.emails.order-ref', ['order_id' => $order->increment_id])"
        :title="trans('shop::app.emails.orders.canceled.title')"
        :name="$order->customer_first_name"
    />

    <x-shop::email.text>
        @lang('shop::app.emails.orders.canceled.intro')
    </x-shop::email.text>

    @if ($paidOnline)
        <x-shop::email.panel>
            @lang('shop::app.emails.orders.canceled.refund-note')
        </x-shop::email.panel>
    @endif

    <x-shop::email.items :items="$order->items" :currency="$order->order_currency_code" />

    <x-shop::email.button :url="route('shop.home.index')">
        @lang('shop::app.emails.continue-shopping')
    </x-shop::email.button>
@endcomponent

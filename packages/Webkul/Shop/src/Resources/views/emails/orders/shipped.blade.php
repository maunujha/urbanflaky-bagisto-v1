{{-- Shipped: courier, AWB and tracking for the pieces in this shipment. --}}
@php
    $order = $shipment->order;

    /* Only AWBs our tracking page knows: a hand-shipped order with another courier would show "not found". */
    $trackable = $shipment->track_number
        && \App\Models\ShiprocketOrder::where('order_id', $shipment->order_id)->where('awb_code', $shipment->track_number)->exists();
@endphp

@component('shop::emails.layout', ['preheader' => trans('shop::app.emails.orders.shipped.preheader', ['order_id' => $order->increment_id])])
    <x-shop::email.heading
        :context="trans('shop::app.emails.order-ref', ['order_id' => $order->increment_id])"
        :title="trans('shop::app.emails.orders.shipped.title')"
        :name="$order->customer_first_name"
    />

    <x-shop::email.text>
        @lang('shop::app.emails.orders.shipped.intro')
    </x-shop::email.text>

    @if ($shipment->carrier_title || $shipment->track_number)
        <x-shop::email.panel>
            @if ($shipment->carrier_title)
                <span style="color:#a3a3a3;">@lang('shop::app.emails.orders.carrier')</span>&nbsp; <strong>{{ $shipment->carrier_title }}</strong><br />
            @endif
            @if ($shipment->track_number)
                <span style="color:#a3a3a3;">@lang('shop::app.emails.orders.awb')</span>&nbsp; <strong>{{ $shipment->track_number }}</strong>
            @endif
        </x-shop::email.panel>
    @endif

    @if ($trackable)
        <x-shop::email.button :url="route('shop.track-order.index', ['awb' => $shipment->track_number])">
            @lang('shop::app.emails.orders.track')
        </x-shop::email.button>
    @endif

    <x-shop::email.items :items="$shipment->items" :currency="$order->order_currency_code" :prices="false" />

    <x-shop::email.order-details :order="$order" />

    @unless ($trackable)
        <x-shop::email.button :url="route('shop.customers.account.orders.view', $order->id)">
            @lang('shop::app.emails.orders.view-order')
        </x-shop::email.button>
    @endunless
@endcomponent

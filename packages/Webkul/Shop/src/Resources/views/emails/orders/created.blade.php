{{--
    Order confirmation. Payment wording only from a verified gateway capture
    ($payment, set by Gabha\Notifications\Mail\OrderConfirmation); cash on
    delivery gets the amount to keep ready instead.
--}}
@component('shop::emails.layout', ['preheader' => trans('shop::app.emails.orders.created.preheader', ['order_id' => $order->increment_id])])
    <x-shop::email.heading
        :context="trans('shop::app.emails.order-ref', ['order_id' => $order->increment_id])"
        :title="trans('shop::app.emails.orders.created.title')"
        :name="$order->customer_first_name"
    />

    <x-shop::email.text>
        @lang('shop::app.emails.orders.created.intro')
    </x-shop::email.text>

    @if (! empty($payment))
        <x-shop::email.panel accent>
            <strong>@lang('shop::app.emails.orders.created.payment-received', [
                'amount'    => core()->formatPrice($payment->amount, $order->order_currency_code),
                'method'    => $paymentTitle ?: 'online payment',
                'reference' => $payment->transaction_id,
            ])</strong>

            @if (! empty($invoiceAttached))
                <br /><span style="color:#a3a3a3;">@lang('shop::app.emails.orders.created.invoice-attached')</span>
            @endif
        </x-shop::email.panel>
    @elseif (! empty($isCod))
        <x-shop::email.panel accent>
            <strong>@lang('shop::app.emails.orders.created.cod-note', [
                'amount' => core()->formatPrice($order->grand_total, $order->order_currency_code),
            ])</strong>
        </x-shop::email.panel>
    @endif

    <x-shop::email.items :items="$order->items" :currency="$order->order_currency_code" />

    <x-shop::email.totals :source="$order" :currency="$order->order_currency_code" />

    <x-shop::email.order-details :order="$order" />

    <x-shop::email.button :url="route('shop.customers.account.orders.view', $order->id)">
        @lang('shop::app.emails.orders.view-order')
    </x-shop::email.button>
@endcomponent

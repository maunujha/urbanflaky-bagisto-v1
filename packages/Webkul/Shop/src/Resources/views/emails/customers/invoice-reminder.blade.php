{{-- Overdue invoice reminder. --}}
@component('shop::emails.layout', ['preheader' => trans('shop::app.emails.customers.reminder.invoice-overdue')])
    <x-shop::email.heading
        :context="trans('shop::app.emails.order-ref', ['order_id' => $invoice->order->increment_id])"
        :title="trans('shop::app.emails.customers.reminder.title')"
        :name="$invoice->order->customer_first_name"
    />

    <x-shop::email.text>
        @lang('shop::app.emails.customers.reminder.invoice-overdue')
    </x-shop::email.text>

    <x-shop::email.totals :source="$invoice" :currency="$invoice->order->order_currency_code" />

    <x-shop::email.text muted>
        @lang('shop::app.emails.customers.reminder.already-paid')
    </x-shop::email.text>

    <x-shop::email.button :url="route('shop.customers.account.orders.view', $invoice->order_id)">
        @lang('shop::app.emails.orders.view-order')
    </x-shop::email.button>
@endcomponent

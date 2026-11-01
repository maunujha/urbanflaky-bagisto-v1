{{-- Invoice email: the PDF is attached; never presented as a payment unless the invoice is paid. --}}
@component('shop::emails.layout', ['preheader' => trans('shop::app.emails.orders.invoiced.preheader', ['invoice_id' => $invoice->increment_id ?? $invoice->id])])
    <x-shop::email.heading
        :context="trans('shop::app.emails.order-ref', ['order_id' => $invoice->order->increment_id])"
        :title="trans('shop::app.emails.orders.invoiced.title')"
        :name="$invoice->order->customer_first_name"
    />

    <x-shop::email.text>
        @lang('shop::app.emails.orders.invoiced.intro', [
            'invoice_id' => $invoice->increment_id ?? $invoice->id,
            'date'       => core()->formatDate($invoice->created_at, 'd M Y'),
        ])
    </x-shop::email.text>

    <x-shop::email.items :items="$invoice->items" :currency="$invoice->order->order_currency_code" />

    <x-shop::email.totals :source="$invoice" :currency="$invoice->order->order_currency_code" :grand-label="trans('shop::app.emails.orders.invoiced.total')" />

    <x-shop::email.order-details :order="$invoice->order" billing />

    <x-shop::email.button :url="route('shop.customers.account.orders.view', $invoice->order_id)">
        @lang('shop::app.emails.orders.view-order')
    </x-shop::email.button>
@endcomponent

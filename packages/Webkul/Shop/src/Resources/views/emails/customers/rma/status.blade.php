{{-- Return request status changed (Accepted, Declined, Received...). --}}
@component('shop::emails.layout', ['preheader' => trans('shop::app.rma.mail.status.subject', ['id' => $rma->id, 'status' => $rma->status?->title])])
    <x-shop::email.heading
        :context="trans('shop::app.emails.order-ref', ['order_id' => $rma->order->increment_id])"
        :title="trans('shop::app.rma.mail.status.heading-status', ['status' => $rma->status?->title])"
        :name="$rma->order->customer_first_name"
    />

    <x-shop::email.text>
        @lang('shop::app.rma.mail.status.intro', ['id' => $rma->id])
    </x-shop::email.text>

    <x-shop::email.panel accent>
        <span style="color:#a3a3a3;">@lang('shop::app.rma.mail.status.status')</span>&nbsp; <strong>{{ $rma->status?->title }}</strong>
    </x-shop::email.panel>

    <x-shop::email.button :url="route('shop.customers.account.rma.view', $rma->id)">
        @lang('shop::app.rma.mail.customer-rma-create.cta')
    </x-shop::email.button>
@endcomponent

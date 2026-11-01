{{-- A reply from the team on a return request. --}}
@php($rma = $rmaMessage->rma)

@component('shop::emails.layout', ['preheader' => trans('shop::app.rma.mail.customer-conversation.preheader', ['id' => $rma->id])])
    <x-shop::email.heading
        :context="trans('shop::app.emails.order-ref', ['order_id' => $rma->order->increment_id])"
        :title="trans('shop::app.rma.mail.customer-conversation.title-reply', ['id' => $rma->id])"
        :name="$rma->order->customer_first_name"
    />

    <x-shop::email.panel>
        {!! nl2br(e(strip_tags($rmaMessage->message))) !!}
    </x-shop::email.panel>

    <x-shop::email.button :url="route('shop.customers.account.rma.view', $rma->id)">
        @lang('shop::app.rma.mail.customer-conversation.cta')
    </x-shop::email.button>
@endcomponent

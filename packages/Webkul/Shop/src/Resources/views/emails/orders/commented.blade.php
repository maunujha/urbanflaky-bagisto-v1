{{-- A note from the team on the customer's order. --}}
@component('shop::emails.layout', ['preheader' => trans('shop::app.emails.orders.commented.preheader', ['order_id' => $comment->order->increment_id])])
    <x-shop::email.heading
        :context="trans('shop::app.emails.order-ref', ['order_id' => $comment->order->increment_id])"
        :title="trans('shop::app.emails.orders.commented.heading')"
        :name="$comment->order->customer_first_name"
    />

    <x-shop::email.panel>
        {!! nl2br(e($comment->comment)) !!}
    </x-shop::email.panel>

    <x-shop::email.button :url="route('shop.customers.account.orders.view', $comment->order_id)">
        @lang('shop::app.emails.orders.view-order')
    </x-shop::email.button>
@endcomponent

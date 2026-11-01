{{-- A note from the team on the customer's account. --}}
@component('shop::emails.layout', ['preheader' => trans('shop::app.emails.customers.commented.preheader')])
    <x-shop::email.heading
        :title="trans('shop::app.emails.customers.commented.title')"
        :name="$customerNote->customer->first_name"
    />

    <x-shop::email.panel>
        {!! nl2br(e($customerNote->note)) !!}
    </x-shop::email.panel>

    <x-shop::email.button :url="route('shop.customers.account.profile.index')">
        @lang('shop::app.emails.customers.commented.cta')
    </x-shop::email.button>
@endcomponent

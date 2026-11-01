{{-- Account created by the team, with sign-in details. --}}
@component('shop::emails.layout', ['preheader' => trans('shop::app.emails.customers.registration.preheader')])
    <x-shop::email.heading
        :title="trans('shop::app.emails.customers.registration.title')"
        :name="$customer->first_name"
    />

    <x-shop::email.text>
        @lang('shop::app.emails.customers.registration.credentials-description')
    </x-shop::email.text>

    <x-shop::email.panel>
        <span style="color:#a3a3a3;">@lang('shop::app.emails.customers.registration.username-email')</span>&nbsp; <strong>{{ $customer->email }}</strong><br />
        <span style="color:#a3a3a3;">@lang('shop::app.emails.customers.registration.password')</span>&nbsp; <strong>{{ $password }}</strong>
    </x-shop::email.panel>

    <x-shop::email.button :url="route('shop.customer.session.index')">
        @lang('shop::app.emails.customers.registration.sign-in')
    </x-shop::email.button>
@endcomponent

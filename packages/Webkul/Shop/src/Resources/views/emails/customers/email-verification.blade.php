{{-- Verify the email address on a new account. $customer is a model, or an array on resend. --}}
@component('shop::emails.layout', ['preheader' => trans('shop::app.emails.customers.verification.preheader')])
    <x-shop::email.heading
        :title="trans('shop::app.emails.customers.verification.heading')"
        :name="data_get($customer, 'first_name')"
    />

    <x-shop::email.text>
        @lang('shop::app.emails.customers.verification.greeting')
    </x-shop::email.text>

    <x-shop::email.text muted>
        @lang('shop::app.emails.customers.verification.description')
    </x-shop::email.text>

    <x-shop::email.button :url="route('shop.customers.verify', data_get($customer, 'token'))">
        @lang('shop::app.emails.customers.verification.verify-email')
    </x-shop::email.button>
@endcomponent

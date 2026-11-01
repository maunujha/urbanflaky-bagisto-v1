{{-- Password reset link. --}}
@component('shop::emails.layout', ['preheader' => trans('shop::app.emails.customers.forgot-password.preheader')])
    <x-shop::email.heading
        :title="trans('shop::app.emails.customers.forgot-password.title')"
        :name="$userName"
    />

    <x-shop::email.text>
        @lang('shop::app.emails.customers.forgot-password.greeting')
    </x-shop::email.text>

    <x-shop::email.button :url="route('shop.customers.reset_password.create', $token)">
        @lang('shop::app.emails.customers.forgot-password.reset-password')
    </x-shop::email.button>

    <x-shop::email.text muted>
        @lang('shop::app.emails.customers.forgot-password.description')
    </x-shop::email.text>
@endcomponent

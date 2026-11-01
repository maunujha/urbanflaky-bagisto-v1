{{-- Security notice: the account password changed. --}}
@component('shop::emails.layout', ['preheader' => trans('shop::app.emails.customers.update-password.preheader')])
    <x-shop::email.heading
        :title="trans('shop::app.emails.customers.update-password.title')"
        :name="$customer->first_name"
    />

    <x-shop::email.text>
        @lang('shop::app.emails.customers.update-password.greeting')
    </x-shop::email.text>

    <x-shop::email.panel>
        @lang('shop::app.emails.customers.update-password.description')
    </x-shop::email.panel>
@endcomponent

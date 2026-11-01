{{-- Welcome to a new account; the optional welcome offer is added by Gabha\Notifications. --}}
@component('shop::emails.layout', ['preheader' => trans('shop::app.emails.customers.registration.preheader')])
    <x-shop::email.heading
        :title="trans('shop::app.emails.customers.registration.title')"
        :name="$customer->first_name"
    />

    <x-shop::email.text>
        @lang('shop::app.emails.customers.registration.intro')
    </x-shop::email.text>

    {{-- Optional welcome offer (Gabha\Notifications; shown only while its coupon is valid). --}}
    @includeWhen(! empty($welcomeOffer), 'notifications::emails.partials.welcome-offer')

    <x-shop::email.button :url="route('shop.home.index')">
        @lang('shop::app.emails.customers.registration.cta')
    </x-shop::email.button>
@endcomponent

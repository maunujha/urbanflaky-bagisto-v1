{{-- Newsletter welcome; marketing, so the footer carries the unsubscribe link. --}}
@component('shop::emails.layout', [
    'preheader'      => trans('shop::app.emails.customers.subscribed.preheader'),
    'unsubscribeUrl' => route('shop.subscription.destroy', $subscribersList->token),
])
    <x-shop::email.heading
        :title="trans('shop::app.emails.customers.subscribed.title')"
        :name="! empty($fullName) ? $fullName : null"
    />

    <x-shop::email.text>
        @lang('shop::app.emails.customers.subscribed.greeting')
    </x-shop::email.text>

    <x-shop::email.text>
        @lang('shop::app.emails.customers.subscribed.description')
    </x-shop::email.text>

    {{-- Optional welcome offer (Gabha\Notifications; shown only while its coupon is valid). --}}
    @includeWhen(! empty($welcomeOffer), 'notifications::emails.partials.welcome-offer')

    <x-shop::email.button :url="route('shop.home.index')">
        @lang('shop::app.emails.customers.subscribed.cta')
    </x-shop::email.button>
@endcomponent

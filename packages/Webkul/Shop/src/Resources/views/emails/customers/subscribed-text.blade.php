@lang('shop::app.emails.dear', ['customer_name' => ! empty($fullName) ? $fullName : $subscribersList->email])

@lang('shop::app.emails.customers.subscribed.greeting')

@lang('shop::app.emails.customers.subscribed.description')
@if (! empty($welcomeOffer))

{{ $welcomeOffer['text'] ?: trans('notifications::app.emails.welcome-offer.default') }}
@lang('notifications::app.emails.welcome-offer.code'): {{ $welcomeOffer['code'] }}
@endif

@lang('shop::app.emails.customers.subscribed.unsubscribe'): {{ route('shop.subscription.destroy', $subscribersList->token) }}

--
{{ config('app.name') }} — a brand by Gabha Enterprise, Dholpur, Rajasthan, India
{{ core()->getContactEmailDetails()['email'] }}

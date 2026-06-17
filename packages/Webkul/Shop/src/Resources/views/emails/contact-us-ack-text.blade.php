@lang('shop::app.emails.contact-us.ack-greeting', ['name' => $contactUs['name']])

@lang('shop::app.emails.contact-us.ack-intro')
@if (! empty($contactUs['topic']))

@lang('shop::app.emails.contact-us.ack-topic'): {{ $contactUs['topic'] }}
@endif

@lang('shop::app.emails.contact-us.ack-your-message'):
{{ $contactUs['message'] }}

@lang('shop::app.emails.contact-us.ack-response-time')

@lang('shop::app.emails.contact-us.ack-cta'): {{ route('shop.home.index') }}

--
{{ config('app.name') }} — a brand by Gabha Enterprise, Dholpur, Rajasthan, India
{{ core()->getContactEmailDetails()['email'] }}

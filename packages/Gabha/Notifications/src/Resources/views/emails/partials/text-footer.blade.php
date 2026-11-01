--
{{ config('app.name') }}, a brand by Gabha Enterprise, Dholpur, Rajasthan, India
{{ core()->getContactEmailDetails()['email'] }}
@if (! empty($unsubscribeUrl))
@lang('notifications::app.emails.unsubscribe'): {{ $unsubscribeUrl }}
@endif

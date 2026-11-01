@lang('notifications::app.emails.win-back.title')

@lang('notifications::app.emails.hello', ['name' => $customer->first_name])

@lang('notifications::app.emails.win-back.body')

@lang('notifications::app.emails.win-back.cta'): {{ $shopUrl }}

@lang('notifications::app.emails.why-marketing')
@include('notifications::emails.partials.text-footer')

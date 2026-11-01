@lang('notifications::app.emails.confirm-subscription.title')

@lang('notifications::app.emails.confirm-subscription.body')

@lang('notifications::app.emails.confirm-subscription.cta'): {{ $confirmUrl }}

@lang('notifications::app.emails.confirm-subscription.ignore')
@include('notifications::emails.partials.text-footer')

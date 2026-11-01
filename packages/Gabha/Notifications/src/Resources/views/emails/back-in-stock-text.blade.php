@lang('notifications::app.emails.back-in-stock.title')

@lang('notifications::app.emails.back-in-stock.body')

{{ $name }}{{ $options ? ' ('.$options.')' : '' }}: {{ $price }}

@lang('notifications::app.emails.back-in-stock.cta'): {{ $url }}

@lang('notifications::app.emails.back-in-stock.why')
@include('notifications::emails.partials.text-footer')

@lang('notifications::app.emails.feedback.title')

@lang('notifications::app.emails.hello', ['name' => $order->customer_first_name])

@lang('notifications::app.emails.feedback.body', ['order_id' => $order->increment_id])

@foreach ($ratings as $rating => $url)
{{ $rating }}: {{ $url }}
@endforeach

@lang('notifications::app.emails.feedback.scale')
@include('notifications::emails.partials.text-footer')

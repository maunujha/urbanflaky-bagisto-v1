@lang('notifications::app.emails.review.title')

@lang('notifications::app.emails.hello', ['name' => $order->customer_first_name])

@lang('notifications::app.emails.review.body')

@foreach ($products as $product)
- {{ $product['name'] }}: {{ $product['url'] }}
@endforeach

@include('notifications::emails.partials.text-footer')

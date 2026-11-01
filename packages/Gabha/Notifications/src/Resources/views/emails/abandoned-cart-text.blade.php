@lang($copy.'.title')

@lang('notifications::app.emails.hello', ['name' => $cart->customer_first_name])

@lang($copy.'.body')

@foreach ($items as $item)
- {{ $item->name }} ({{ trans('notifications::app.emails.cart.qty', ['qty' => $item->quantity]) }})
@endforeach

@lang('notifications::app.emails.cart.cta'): {{ $recoverUrl }}

@lang('notifications::app.emails.why-marketing')
@include('notifications::emails.partials.text-footer')

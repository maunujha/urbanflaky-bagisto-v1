@lang($copy.'.title')

@lang('notifications::app.emails.hello', ['name' => $cart->customer_first_name])

@lang($copy.'.body')

@foreach ($items as $item)
- {{ $item['name'] }}{{ $item['options'] ? ' ('.$item['options'].')' : '' }}: {{ trans('notifications::app.emails.cart.qty-price', ['qty' => $item['qty'], 'price' => $item['price']]) }} = {{ $item['total'] }}
@endforeach

@lang('notifications::app.emails.cart.total'): {{ $total }}

@lang('notifications::app.emails.cart.cta'): {{ $recoverUrl }}

@lang('notifications::app.emails.why-marketing')
@include('notifications::emails.partials.text-footer')

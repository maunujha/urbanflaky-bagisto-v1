{{-- Delivery address, payment method and delivery method for an order. --}}
@props(['order', 'billing' => false])

@php
    $address = fn ($a) => $a ? [
        $a->name,
        $a->company_name,
        $a->address,
        trim($a->city.', '.$a->state.' '.$a->postcode, ' ,'),
        $a->phone,
    ] : [];

    $blocks = [
        ['title' => trans('shop::app.emails.orders.shipping-address'), 'lines' => $address($order->shipping_address)],
        ['title' => trans('shop::app.emails.orders.payment'), 'lines' => [
            core()->getConfigData('sales.payment_methods.'.$order->payment?->method.'.title') ?: $order->payment?->method_title,
            $order->shipping_title,
        ]],
    ];

    if ($billing) {
        $blocks[] = ['title' => trans('shop::app.emails.orders.billing-address'), 'lines' => $address($order->billing_address)];
    }
@endphp

<x-shop::email.details :blocks="$blocks" />

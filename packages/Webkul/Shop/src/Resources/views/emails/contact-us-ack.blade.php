{{-- "We got your message": recap of what the customer sent. --}}
@component('shop::emails.layout', ['preheader' => trans('shop::app.emails.contact-us.ack-response-time')])
    <x-shop::email.heading
        :title="trans('shop::app.emails.contact-us.ack-title')"
        :name="$contactUs['name']"
    />

    <x-shop::email.text>
        @lang('shop::app.emails.contact-us.ack-intro')
    </x-shop::email.text>

    <x-shop::email.panel>
        @if (! empty($contactUs['topic']))
            <span style="color:#a3a3a3;">@lang('shop::app.emails.contact-us.ack-topic')</span><br />
            <strong>{{ $contactUs['topic'] }}</strong><br /><br />
        @endif

        <span style="color:#a3a3a3;">@lang('shop::app.emails.contact-us.ack-your-message')</span><br />
        {!! nl2br(e($contactUs['message'])) !!}
    </x-shop::email.panel>

    <x-shop::email.text>
        @lang('shop::app.emails.contact-us.ack-response-time')
    </x-shop::email.text>

    <x-shop::email.button :url="route('shop.home.index')" variant="secondary">
        @lang('shop::app.emails.contact-us.ack-cta')
    </x-shop::email.button>
@endcomponent

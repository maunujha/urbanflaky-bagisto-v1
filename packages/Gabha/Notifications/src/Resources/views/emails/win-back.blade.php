@component('shop::emails.layout')
    @include('notifications::emails.partials.heading', [
        'title' => trans('notifications::app.emails.win-back.title'),
        'name'  => $customer->first_name,
    ])

    <p style="margin:0; font-size:16px; color:#384860; line-height:24px;">
        @lang('notifications::app.emails.win-back.body')
    </p>

    @include('notifications::emails.partials.button', [
        'url'   => $shopUrl,
        'label' => trans('notifications::app.emails.win-back.cta'),
    ])

    @include('notifications::emails.partials.unsubscribe', [
        'reason' => trans('notifications::app.emails.why-marketing'),
    ])
@endcomponent

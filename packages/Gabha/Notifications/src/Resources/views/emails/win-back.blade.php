@component('shop::emails.layout', [
    'preheader'      => trans('notifications::app.emails.win-back.body'),
    'unsubscribeUrl' => $unsubscribeUrl,
])
    <x-shop::email.heading
        :title="trans('notifications::app.emails.win-back.title')"
        :name="$customer->first_name"
    />

    <x-shop::email.text>
        @lang('notifications::app.emails.win-back.body')
    </x-shop::email.text>

    <x-shop::email.button :url="$shopUrl">
        @lang('notifications::app.emails.win-back.cta')
    </x-shop::email.button>
@endcomponent

@component('shop::emails.layout', ['preheader' => trans('notifications::app.emails.confirm-subscription.body')])
    <x-shop::email.heading :title="trans('notifications::app.emails.confirm-subscription.title')" />

    <x-shop::email.text>
        @lang('notifications::app.emails.confirm-subscription.body')
    </x-shop::email.text>

    <x-shop::email.button :url="$confirmUrl">
        @lang('notifications::app.emails.confirm-subscription.cta')
    </x-shop::email.button>

    <x-shop::email.text muted>
        @lang('notifications::app.emails.confirm-subscription.ignore')
    </x-shop::email.text>
@endcomponent

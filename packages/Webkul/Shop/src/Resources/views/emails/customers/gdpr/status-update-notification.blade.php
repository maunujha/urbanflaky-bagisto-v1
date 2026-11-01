{{-- Data request status changed. --}}
@component('shop::emails.layout', ['preheader' => trans('shop::app.emails.customers.gdpr.status-update.subject')])
    <x-shop::email.heading
        :title="trans('shop::app.emails.customers.gdpr.status-update.subject')"
        :name="$gdprRequest->customer->first_name"
    />

    <x-shop::email.text>
        {{ trans('shop::app.emails.customers.gdpr.status-update.summary') }}
    </x-shop::email.text>

    <x-shop::email.details :blocks="[
        ['title' => trans('shop::app.emails.customers.gdpr.status-update.request-type'), 'lines' => [ucfirst($gdprRequest->type)]],
        ['title' => trans('shop::app.emails.customers.gdpr.status-update.request-status'), 'lines' => [ucfirst($gdprRequest->status)]],
    ]" />

    @if ($gdprRequest->message)
        <x-shop::email.panel>{{ $gdprRequest->message }}</x-shop::email.panel>
    @endif
@endcomponent

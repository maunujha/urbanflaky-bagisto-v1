{{-- Data request (update / delete) received. --}}
@component('shop::emails.layout', ['preheader' => trans('shop::app.emails.customers.gdpr.new-request.subject')])
    <x-shop::email.heading
        :title="trans('shop::app.emails.customers.gdpr.new-request.subject')"
        :name="$gdprRequest->customer->first_name"
    />

    <x-shop::email.text>
        {{ $gdprRequest->type == 'update' ? trans('shop::app.emails.customers.gdpr.new-request.update-summary') : trans('shop::app.emails.customers.gdpr.new-request.delete-summary') }}
    </x-shop::email.text>

    <x-shop::email.details :blocks="[
        ['title' => trans('shop::app.emails.customers.gdpr.new-request.request-type'), 'lines' => [ucfirst($gdprRequest->type)]],
        ['title' => trans('shop::app.emails.customers.gdpr.new-request.request-status'), 'lines' => [ucfirst($gdprRequest->status)]],
    ]" />

    @if ($gdprRequest->message)
        <x-shop::email.panel>{{ $gdprRequest->message }}</x-shop::email.panel>
    @endif
@endcomponent

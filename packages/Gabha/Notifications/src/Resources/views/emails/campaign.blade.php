{{-- Admin campaign: the template's HTML (Marketing → Email Templates) inside the store layout. --}}
@component('shop::emails.layout', [
    'preheader'      => $campaign->subject,
    'unsubscribeUrl' => $unsubscribeUrl,
])
    <div style="font-size:16px; line-height:26px; color:#f5f5f5;">
        {!! $html !!}
    </div>
@endcomponent

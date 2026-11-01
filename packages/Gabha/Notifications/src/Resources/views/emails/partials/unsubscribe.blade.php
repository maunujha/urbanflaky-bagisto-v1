@if (! empty($unsubscribeUrl))
    <p style="margin:0 0 8px 0; font-size:12px; color:#9ca3af; line-height:18px;">
        @if (! empty($reason))
            {{ $reason }}
        @endif

        <a href="{{ $unsubscribeUrl }}" style="color:#6b7280; text-decoration:underline;">{{ $label ?? trans('notifications::app.emails.unsubscribe') }}</a>
    </p>
@endif

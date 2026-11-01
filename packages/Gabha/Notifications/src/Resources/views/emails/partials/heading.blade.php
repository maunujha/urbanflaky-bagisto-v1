<p class="uf-h1" style="margin:0 0 20px 0; font-family:'Poppins', Arial, sans-serif; font-size:24px; font-weight:700; color:#0a0a0a; line-height:32px;">
    {{ $title }}
</p>

@if (! empty($name))
    <p style="margin:0 0 12px 0; font-size:16px; color:#384860; line-height:24px;">
        @lang('notifications::app.emails.hello', ['name' => $name])
    </p>
@endif

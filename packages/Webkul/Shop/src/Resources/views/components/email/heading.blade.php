{{-- Email title block: optional context line (e.g. "Order #1042"), title, optional greeting. --}}
@props(['title', 'context' => null, 'name' => null])

@if ($context)
    <p style="margin:0 0 10px 0; font-size:13px; line-height:18px; font-weight:600; color:#a3a3a3;">{{ $context }}</p>
@endif

<h1 class="uf-h1" style="margin:0 0 22px 0; font-family:'Poppins', Arial, sans-serif; font-size:28px; line-height:34px; font-weight:700; letter-spacing:-0.2px; color:#f5f5f5;">{{ $title }}</h1>

@if ($name)
    <p style="margin:0 0 14px 0; font-size:16px; line-height:26px; color:#f5f5f5;">@lang('shop::app.emails.hello', ['name' => $name])</p>
@endif

{{--
    Call to action. "primary" is the lime signal: one per email.
    "secondary" is the outline button for any second action.
--}}
@props(['url', 'variant' => 'primary', 'align' => 'left'])

@php
    $primary = $variant === 'primary';
@endphp

<table role="presentation" cellpadding="0" cellspacing="0" border="0" class="uf-cta" align="{{ $align }}" style="margin:8px 0 28px 0;">
    <tr>
        <td bgcolor="{{ $primary ? '#c7eb31' : '#141414' }}" style="border-radius:2px; background-color:{{ $primary ? '#c7eb31' : '#141414' }}; {{ $primary ? '' : 'border:1px solid #f0f0f0;' }}">
            <a
                href="{{ $url }}"
                target="_blank"
                style="display:inline-block; padding:15px 30px; font-family:'Poppins', Arial, sans-serif; font-size:13px; line-height:16px; font-weight:700; letter-spacing:2px; text-transform:uppercase; text-decoration:none; color:{{ $primary ? '#0a0a0a' : '#f0f0f0' }}; border-radius:2px;"
            >{{ $slot }}</a>
        </td>
    </tr>
</table>
<div style="clear:both; line-height:0; font-size:0;">&nbsp;</div>

{{-- A raised note inside the email: payment status, COD amount, an offer, a quoted message. --}}
@props(['accent' => false])

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px 0;">
    <tr>
        <td bgcolor="#1c1c1c" style="background-color:#1c1c1c; border:1px solid {{ $accent ? '#c7eb31' : '#262626' }}; border-radius:4px; padding:18px 20px; font-size:15px; line-height:23px; color:#f5f5f5;">
            {{ $slot }}
        </td>
    </tr>
</table>

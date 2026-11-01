{{-- Welcome offer block (shown only while its coupon is valid; see Services\WelcomeOffer). --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:4px 0 26px 0;">
    <tr>
        <td bgcolor="#1c1c1c" style="background-color:#1c1c1c; border:1px solid #c7eb31; border-radius:4px; padding:22px 24px;">
            <p style="margin:0; font-size:15px; line-height:23px; color:#f5f5f5;">
                {{ $welcomeOffer['text'] ?: trans('notifications::app.emails.welcome-offer.default') }}
            </p>
            <p style="margin:14px 0 2px 0; font-size:12px; line-height:16px; font-weight:600; letter-spacing:1.5px; text-transform:uppercase; color:#a3a3a3;">
                @lang('notifications::app.emails.welcome-offer.code')
            </p>
            <p style="margin:0; font-family:'Poppins', Arial, sans-serif; font-size:24px; line-height:30px; font-weight:800; letter-spacing:4px; color:#c7eb31;">
                {{ $welcomeOffer['code'] }}
            </p>
        </td>
    </tr>
</table>

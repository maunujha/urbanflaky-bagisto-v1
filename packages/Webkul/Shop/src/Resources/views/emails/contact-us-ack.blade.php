@component('shop::emails.layout')
    <p class="uf-h1" style="margin:0 0 18px 0; font-family:'Poppins', Arial, sans-serif; font-weight:700; font-size:24px; color:#0a0a0a; line-height:30px;">
        @lang('shop::app.emails.contact-us.ack-greeting', ['name' => $contactUs['name']]) 👋
    </p>

    <p style="margin:0 0 22px 0; font-size:16px; color:#384860; line-height:26px;">
        @lang('shop::app.emails.contact-us.ack-intro')
    </p>

    {{-- Recap of what the customer submitted --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 26px 0; background-color:#f7f7f5; border-radius:10px;">
        <tr>
            <td style="padding:20px 22px;">
                @if (! empty($contactUs['topic']))
                    <p style="margin:0 0 8px 0; font-size:12px; letter-spacing:1px; text-transform:uppercase; color:#8a8a8a; font-weight:600;">
                        @lang('shop::app.emails.contact-us.ack-topic')
                    </p>
                    <p style="margin:0 0 16px 0; font-size:15px; color:#0a0a0a; font-weight:600; line-height:22px;">
                        {{ $contactUs['topic'] }}
                    </p>
                @endif

                <p style="margin:0 0 8px 0; font-size:12px; letter-spacing:1px; text-transform:uppercase; color:#8a8a8a; font-weight:600;">
                    @lang('shop::app.emails.contact-us.ack-your-message')
                </p>
                <p style="margin:0; font-size:15px; color:#384860; line-height:24px;">
                    {{ $contactUs['message'] }}
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 30px 0; font-size:16px; color:#384860; line-height:26px;">
        @lang('shop::app.emails.contact-us.ack-response-time')
    </p>

    {{-- CTA --}}
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="uf-cta" style="margin:0 0 6px 0;">
        <tr>
            <td align="center" bgcolor="#0a0a0a" style="border-radius:6px;">
                <a href="{{ route('shop.home.index') }}" target="_blank"
                   style="display:inline-block; padding:15px 40px; font-family:'Poppins', Arial, sans-serif; font-size:14px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:#c7eb31; text-decoration:none; border-radius:6px;">
                    @lang('shop::app.emails.contact-us.ack-cta')
                </a>
            </td>
        </tr>
    </table>
@endcomponent

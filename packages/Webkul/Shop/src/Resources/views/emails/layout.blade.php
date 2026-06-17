<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="x-apple-disable-message-reformatting" />
        <meta name="color-scheme" content="light only" />
        <meta name="supported-color-schemes" content="light only" />
        <title>{{ config('app.name') }}</title>

        <!--[if mso]>
        <noscript>
            <xml>
                <o:OfficeDocumentSettings>
                    <o:PixelsPerInch>96</o:PixelsPerInch>
                </o:OfficeDocumentSettings>
            </xml>
        </noscript>
        <![endif]-->

        <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
            href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap"
            rel="stylesheet"
        />

        <style type="text/css">
            /* Client resets */
            body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
            table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
            img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
            body { margin: 0 !important; padding: 0 !important; width: 100% !important; }

            a { color: #0a0a0a; }
            .uf-link { color: #0a0a0a; font-weight: 600; text-decoration: underline; }

            /* Mobile */
            @media only screen and (max-width: 620px) {
                .uf-container { width: 100% !important; }
                .uf-px { padding-left: 24px !important; padding-right: 24px !important; }
                .uf-py { padding-top: 28px !important; padding-bottom: 28px !important; }
                .uf-wordmark { font-size: 26px !important; letter-spacing: 6px !important; }
                .uf-h1 { font-size: 22px !important; }
                .uf-cta a { display: block !important; width: 100% !important; box-sizing: border-box !important; }
            }
        </style>
    </head>

    <body style="margin:0; padding:0; background-color:#0a0a0a; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
        <!-- Hidden preheader (improves inbox preview, not rendered in body) -->
        <div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#0a0a0a; opacity:0;">
            {{ config('app.name') }} — Premium essentials by Gabha Enterprise.
        </div>

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#0a0a0a;">
            <tr>
                <td align="center" style="padding:32px 16px;">

                    <table role="presentation" class="uf-container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; margin:0 auto;">

                        <!-- ===== Header / brand band ===== -->
                        <tr>
                            <td align="center" style="background-color:#0a0a0a; padding:8px 30px 28px 30px;">
                                <a href="{{ route('shop.home.index') }}" style="text-decoration:none;" target="_blank">
                                    <span class="uf-wordmark" style="display:inline-block; font-family:'Poppins', Arial, sans-serif; font-size:30px; font-weight:800; letter-spacing:9px; color:#ffffff; text-transform:uppercase;">
                                        Urban<span style="color:#c7eb31;">flaky</span>
                                    </span>
                                </a>
                                <div style="margin:14px auto 0 auto; width:44px; height:3px; background-color:#c7eb31; border-radius:3px; line-height:3px; font-size:0;">&nbsp;</div>
                                <div style="margin-top:12px; font-size:11px; letter-spacing:3px; color:#8a8a8a; text-transform:uppercase; font-weight:600;">
                                    Premium Essentials
                                </div>
                            </td>
                        </tr>

                        <!-- ===== Content card ===== -->
                        <tr>
                            <td class="uf-px uf-py" style="background-color:#ffffff; border-radius:14px; padding:44px 44px 36px 44px; border-top:4px solid #c7eb31;">

                                <!-- Per-email content -->
                                {{ $slot }}

                                <!-- In-card help line -->
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td style="padding-top:8px; border-top:1px solid #ececec;">
                                            <p style="margin:24px 0 0 0; font-size:14px; color:#6b7280; line-height:22px;">
                                                @lang('shop::app.emails.thanks', [
                                                    'link' => 'mailto:' . core()->getContactEmailDetails()['email'],
                                                    'email' => core()->getContactEmailDetails()['email'],
                                                    'style' => 'color:#0a0a0a; font-weight:600; text-decoration:underline;'
                                                ])
                                            </p>
                                        </td>
                                    </tr>
                                </table>

                            </td>
                        </tr>

                        <!-- ===== Footer ===== -->
                        <tr>
                            <td align="center" style="padding:28px 30px 8px 30px;">
                                <div style="font-family:'Poppins', Arial, sans-serif; font-size:15px; font-weight:700; letter-spacing:4px; color:#ffffff; text-transform:uppercase;">
                                    Urbanflaky
                                </div>
                                <p style="margin:10px 0 0 0; font-size:12px; line-height:20px; color:#8a8a8a;">
                                    A brand by Gabha Enterprise &middot; Dholpur, Rajasthan, India
                                </p>
                                <p style="margin:6px 0 0 0; font-size:12px; line-height:20px; color:#8a8a8a;">
                                    Need help? <a href="mailto:{{ core()->getContactEmailDetails()['email'] }}" style="color:#c7eb31; text-decoration:none; font-weight:600;">{{ core()->getContactEmailDetails()['email'] }}</a>
                                </p>
                                <p style="margin:18px 0 0 0; font-size:11px; line-height:18px; color:#5a5a5a;">
                                    You received this email because you interacted with {{ config('app.name') }}.
                                </p>
                            </td>
                        </tr>

                    </table>

                </td>
            </tr>
        </table>
    </body>
</html>

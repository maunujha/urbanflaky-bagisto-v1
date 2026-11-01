{{--
    Urbanflaky email layout: every customer email renders inside it.

    "The Night Drop" in email form: void-black canvas, a charcoal panel, paper
    text and one lime signal (the primary button, from x-shop::email.button).
    Email-client safe: tables, inline styles, 600px, a stacking class for
    phones, and a dark colour-scheme declaration so clients do not re-invert.

    Optional data: $preheader (inbox preview line), $unsubscribeUrl (adds the
    opt-out line to the footer), $unsubscribeReason / $unsubscribeLabel (its
    wording, for opt-outs that are not the newsletter).
--}}
@php
    $brand = config('notifications.brand', []);
    $supportEmail = core()->getContactEmailDetails()['email'];
    $supportPhone = core()->getConfigData('sales.shipping.origin.telephone');
    $home = route('shop.home.index');
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="x-apple-disable-message-reformatting" />
        <meta name="format-detection" content="telephone=no, address=no, email=no, date=no" />
        <meta name="color-scheme" content="dark" />
        <meta name="supported-color-schemes" content="dark" />
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

        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

        <style type="text/css">
            :root { color-scheme: dark; supported-color-schemes: dark; }
            body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
            table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
            img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
            body { margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #0a0a0a; }
            a { color: #f5f5f5; }
            a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; }

            @media only screen and (max-width: 620px) {
                .uf-container { width: 100% !important; }
                .uf-px { padding-left: 22px !important; padding-right: 22px !important; }
                .uf-pt { padding-top: 30px !important; }
                .uf-h1 { font-size: 24px !important; line-height: 30px !important; }
                .uf-stack { display: block !important; width: 100% !important; }
                .uf-stack-gap { padding-left: 0 !important; padding-top: 20px !important; }
                .uf-cta a { display: block !important; text-align: center !important; }
                .uf-hide-sm { display: none !important; }
            }
        </style>
    </head>

    <body style="margin:0; padding:0; background-color:#0a0a0a; font-family:'Poppins', Arial, Helvetica, sans-serif; color:#f5f5f5;">
        {{-- Inbox preview line, then filler so the client does not pull body text into it. --}}
        <div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#0a0a0a; opacity:0;">
            {{ $preheader ?? ($brand['tagline'] ?? config('app.name')) }}
            &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847; &#8199;&#65279;&#847;
        </div>

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#0a0a0a" style="background-color:#0a0a0a;">
            <tr>
                <td align="center" style="padding:28px 12px 36px 12px;">

                    <table role="presentation" class="uf-container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; margin:0 auto;">

                        {{-- Wordmark --}}
                        <tr>
                            <td align="center" style="padding:6px 24px 26px 24px;">
                                <a href="{{ $home }}" target="_blank" style="text-decoration:none; color:#f5f5f5;">
                                    <span style="font-family:'Poppins', Arial, sans-serif; font-size:24px; font-weight:800; letter-spacing:7px; text-transform:uppercase; color:#f5f5f5;">Urban<span style="color:#c7eb31;">flaky</span></span>
                                </a>
                            </td>
                        </tr>

                        {{-- Panel --}}
                        <tr>
                            <td class="uf-px uf-pt" bgcolor="#141414" style="background-color:#141414; border:1px solid #262626; border-radius:6px; padding:40px 40px 34px 40px;">
                                {{ $slot }}
                            </td>
                        </tr>

                        {{-- Support --}}
                        <tr>
                            <td class="uf-px" style="padding:30px 40px 0 40px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td style="border-top:1px solid #262626; padding-top:24px; font-size:13px; line-height:21px; color:#a3a3a3;">
                                            <span style="color:#f5f5f5; font-weight:600;">@lang('shop::app.emails.footer.help')</span><br />
                                            <a href="mailto:{{ $supportEmail }}" style="color:#f5f5f5; text-decoration:underline;">{{ $supportEmail }}</a>
                                            @if ($supportPhone)
                                                &nbsp;·&nbsp;<a href="tel:+91{{ preg_replace('/\D/', '', $supportPhone) }}" style="color:#f5f5f5; text-decoration:underline;">{{ $supportPhone }}</a>
                                            @endif
                                            @if (! empty($brand['support_hours']))
                                                <br />{{ $brand['support_hours'] }}
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        {{-- Social --}}
                        @if (! empty($brand['social']))
                            <tr>
                                <td class="uf-px" style="padding:18px 40px 0 40px; font-size:12px; line-height:20px; letter-spacing:2px; text-transform:uppercase;">
                                    @foreach ($brand['social'] as $network => $url)
                                        <a href="{{ $url }}" target="_blank" style="color:#f5f5f5; text-decoration:none; font-weight:600;">{{ $network }}</a>@if (! $loop->last)<span style="color:#525252;">&nbsp;&nbsp;/&nbsp;&nbsp;</span>@endif
                                    @endforeach
                                </td>
                            </tr>
                        @endif

                        {{-- Company --}}
                        <tr>
                            <td class="uf-px" style="padding:18px 40px 0 40px; font-size:12px; line-height:19px; color:#8a8a8a;">
                                <a href="{{ $home }}" target="_blank" style="color:#a3a3a3; text-decoration:none;">{{ preg_replace('#^https?://#', '', rtrim($home, '/')) }}</a><br />
                                {{ $brand['company'] ?? config('app.name') }}

                                @if (! empty($unsubscribeUrl))
                                    <br /><br />
                                    {{ $unsubscribeReason ?? trans('shop::app.emails.footer.marketing') }}
                                    <a href="{{ $unsubscribeUrl }}" target="_blank" style="color:#a3a3a3; text-decoration:underline;">{{ $unsubscribeLabel ?? trans('shop::app.emails.footer.unsubscribe') }}</a>
                                @endif
                            </td>
                        </tr>

                    </table>

                </td>
            </tr>
        </table>
    </body>
</html>

@component('shop::emails.layout')
    <p style="font-size: 18px;color: #111;font-weight: 600;margin-bottom: 20px;">
        New contact form enquiry
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%;border-collapse: collapse;font-size: 15px;color: #384860;line-height: 22px;margin-bottom: 24px;">
        <tr>
            <td style="padding: 8px 12px 8px 0;border-bottom: 1px solid #eee;width: 110px;color: #777;vertical-align: top;">Name</td>
            <td style="padding: 8px 0;border-bottom: 1px solid #eee;">{{ $contactUs['name'] }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 12px 8px 0;border-bottom: 1px solid #eee;color: #777;vertical-align: top;">Email</td>
            <td style="padding: 8px 0;border-bottom: 1px solid #eee;"><a href="mailto:{{ $contactUs['email'] }}">{{ $contactUs['email'] }}</a></td>
        </tr>
        <tr>
            <td style="padding: 8px 12px 8px 0;border-bottom: 1px solid #eee;color: #777;vertical-align: top;">Phone</td>
            <td style="padding: 8px 0;border-bottom: 1px solid #eee;">
                @if (! empty($contactUs['contact']))
                    <a href="tel:{{ $contactUs['contact'] }}">{{ $contactUs['contact'] }}</a> (OTP verified)
                @else
                    &mdash;
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 12px 8px 0;border-bottom: 1px solid #eee;color: #777;vertical-align: top;">Topic</td>
            <td style="padding: 8px 0;border-bottom: 1px solid #eee;">{{ $contactUs['topic'] ?: '—' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 12px 8px 0;color: #777;vertical-align: top;">Message</td>
            <td style="padding: 8px 0;">{!! nl2br(e($contactUs['message'])) !!}</td>
        </tr>
    </table>

    <p style="font-size: 13px;color: #777;line-height: 20px;">
        Reply to this email to answer {{ $contactUs['name'] }} directly.
    </p>
@endcomponent

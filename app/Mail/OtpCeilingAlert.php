<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent at most once per ceiling per day when the SMS OTP circuit-breaker trips.
 */
class OtpCeilingAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $bucket, public string $flow) {}

    public function envelope(): Envelope
    {
        $admin = core()->getAdminEmailDetails();

        return new Envelope(
            to: [new Address($admin['email'], $admin['name'])],
            subject: 'Urbanflaky alert: daily SMS OTP limit reached',
        );
    }

    public function content(): Content
    {
        $limits = config('otp_security.send');

        $html = '<p>The daily SMS OTP safety limit was reached on '.e(now()->format('d M Y, H:i')).' IST.</p>'
            .'<ul>'
            .'<li>Limit hit: <strong>'.e($this->bucket).'</strong> (triggered from the '.e($this->flow).' flow)</li>'
            .'<li>Current ceilings: '.(int) $limits['site_daily_max'].' total, '
            .(int) $limits['site_daily_max_non_checkout'].' for contact/login/signup, '
            .(int) $limits['site_daily_max_contact'].' for contact</li>'
            .'</ul>'
            .'<p>Further OTP requests in that group are refused until midnight IST. This usually means automated '
            .'abuse; check <code>storage/logs</code> for <code>otp.send.limited</code> entries. If it is genuine '
            .'traffic, raise the matching <code>OTP_SITE_DAILY_CEILING*</code> value in .env, then run '
            .'<code>php artisan config:cache</code> and restart the workers.</p>';

        return new Content(htmlString: $html);
    }
}

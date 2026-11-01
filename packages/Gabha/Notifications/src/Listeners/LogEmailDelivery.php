<?php

namespace Gabha\Notifications\Listeners;

use Gabha\Notifications\Models\EmailLog;
use Gabha\Notifications\Services\EmailDispatcher;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Str;
use Throwable;

/**
 * Records every email in email_logs: updates rows EmailDispatcher created
 * (matched by the X-UF-Log-Id header) and adds rows for Bagisto's own mails.
 * Logging must never break sending, so every path swallows its own errors.
 */
class LogEmailDelivery
{
    /**
     * Cancels any email (Bagisto's included) whose recipients are all
     * placeholder addresses: returning false stops the send.
     */
    public function sending(MessageSending $event): ?bool
    {
        $recipients = $event->message->getTo();

        foreach ($recipients as $address) {
            if (EmailDispatcher::deliverable($address->getAddress())) {
                return null;
            }
        }

        return $recipients ? false : null;
    }

    public function sent(MessageSent $event): void
    {
        try {
            $logId = $event->message->getHeaders()->get('X-UF-Log-Id')?->getBodyAsString();

            if ($logId) {
                EmailLog::whereKey((int) $logId)->update([
                    'status'  => EmailLog::STATUS_SENT,
                    'sent_at' => now(),
                    'error'   => null,
                ]);

                return;
            }

            $mailable = $event->data['__laravel_mailable'] ?? null;

            EmailLog::create([
                'type'      => $mailable ? $this->typeOf($mailable) : 'raw',
                'mailable'  => $mailable ?? 'raw',
                'recipient' => mb_strtolower($event->message->getTo()[0]?->getAddress() ?? ''),
                'subject'   => mb_substr((string) $event->message->getSubject(), 0, 255),
                'status'    => EmailLog::STATUS_SENT,
                'sent_at'   => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function failed(JobFailed $event): void
    {
        try {
            $payload = $event->job->payload();

            if (($payload['data']['commandName'] ?? null) !== SendQueuedMailable::class) {
                return;
            }

            $error = mb_substr($event->exception->getMessage(), 0, 1000);

            $mailable = $this->mailableOf($payload);

            if ($mailable && property_exists($mailable, 'emailLogId') && $mailable->emailLogId) {
                EmailLog::whereKey($mailable->emailLogId)->update(['status' => EmailLog::STATUS_FAILED, 'error' => $error]);

                return;
            }

            $class = $payload['displayName'] ?? 'unknown';

            EmailLog::create([
                'type'      => $this->typeOf($class),
                'mailable'  => $class,
                'recipient' => '',
                'status'    => EmailLog::STATUS_FAILED,
                'error'     => $error,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    protected function mailableOf(array $payload): ?object
    {
        try {
            /* Restoring models can itself fail (the cause of the failure). */
            return unserialize($payload['data']['command'])->mailable ?? null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Webkul\Shop\Mail\Order\ShippedNotification → shop.shipped
     */
    protected function typeOf(string $class): string
    {
        $area = str_contains($class, '\\Admin\\') ? 'admin' : 'shop';

        return $area.'.'.Str::snake(Str::beforeLast(class_basename($class), 'Notification') ?: class_basename($class));
    }
}

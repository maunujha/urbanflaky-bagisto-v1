<?php

namespace Gabha\Notifications\Listeners;

use Gabha\Notifications\Models\EmailLog;
use Gabha\Notifications\Models\EmailTemplateSetting;
use Gabha\Notifications\Services\EmailDispatcher;
use Gabha\Notifications\Support\HtmlToText;
use Gabha\Notifications\Templates\EmailCatalog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
     * Last stop before any email (Bagisto's included) leaves:
     *
     * - cancels it when every recipient is a placeholder address (returning
     *   false stops the send);
     * - cancels it when its log row is already sent: a retried or replayed
     *   job never emails the customer twice;
     * - applies the subject edited in admin (Email Templates);
     * - adds a plain-text part when the email has none.
     */
    public function sending(MessageSending $event): ?bool
    {
        $recipients = $event->message->getTo();

        $deliverable = collect($recipients)->contains(fn ($address) => EmailDispatcher::deliverable($address->getAddress()));

        if ($recipients && ! $deliverable) {
            return false;
        }

        $logId = $event->message->getHeaders()->get('X-UF-Log-Id')?->getBodyAsString();

        if ($logId && EmailLog::whereKey((int) $logId)->where('status', EmailLog::STATUS_SENT)->exists()) {
            return false;
        }

        try {
            $key = app(EmailCatalog::class)->keyFor($event->data);
            $custom = $key ? (EmailTemplateSetting::subjects()[$key] ?? null) : null;

            if ($custom) {
                $event->message->subject(EmailCatalog::compile($custom, $event->data));
            }

            $html = $event->message->getHtmlBody();

            if (is_string($html) && $html !== '' && ! $event->message->getTextBody()) {
                $event->message->text(HtmlToText::convert($html));
            }
        } catch (Throwable $e) {
            /* Presentation extras must never stop an email. */
            report($e);
        }

        return null;
    }

    public function sent(MessageSent $event): void
    {
        try {
            $logId = $event->message->getHeaders()->get('X-UF-Log-Id')?->getBodyAsString();

            if ($logId) {
                /* The subject as actually sent (an admin edit may have changed it). */
                EmailLog::whereKey((int) $logId)->update([
                    'status'  => EmailLog::STATUS_SENT,
                    'subject' => mb_substr((string) $event->message->getSubject(), 0, 255),
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

            /* Its customer, order or product was deleted: a retry can never succeed. */
            $gone = $event->exception instanceof ModelNotFoundException;

            $error = $gone ? 'Not sent: the customer, order or product no longer exists' : mb_substr($event->exception->getMessage(), 0, 1000);
            $uuid = $gone ? null : $event->job->uuid();

            if ($logId = $this->logIdOf($payload)) {
                EmailLog::whereKey($logId)->update(['status' => EmailLog::STATUS_FAILED, 'error' => $error, 'job_uuid' => $uuid]);

                return;
            }

            $class = $payload['displayName'] ?? 'unknown';

            EmailLog::create([
                'type'      => $this->typeOf($class),
                'mailable'  => $class,
                'recipient' => '',
                'status'    => EmailLog::STATUS_FAILED,
                'error'     => $error,
                'job_uuid'  => $uuid,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Read from the serialized job, never by unserializing it: restoring its
     * models fails when the customer, order or product was deleted, which is
     * often why the job failed in the first place.
     */
    protected function logIdOf(array $payload): ?int
    {
        return preg_match('/"emailLogId";i:(\d+);/', (string) ($payload['data']['command'] ?? ''), $match)
            ? (int) $match[1]
            : null;
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

<?php

namespace Gabha\Notifications\Services;

use Gabha\Notifications\Models\EmailLog;
use Gabha\Notifications\Models\EmailSuppression;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * The single way automated emails leave the store: logs the email, refuses a
 * second send for the same dedupe key, and queues it once the surrounding
 * database transaction (if any) has committed.
 */
class EmailDispatcher
{
    /**
     * @return bool whether the email was queued (false: duplicate or failure)
     */
    public function send(
        Mailable $mailable,
        string $type,
        ?string $dedupeKey = null,
        ?Model $related = null,
        string $category = EmailLog::CATEGORY_TRANSACTIONAL,
    ): bool {
        if ($dedupeKey && $this->alreadySent($dedupeKey)) {
            return false;
        }

        $recipient = $this->recipient($mailable);

        if (! self::deliverable($recipient)) {
            return false;
        }

        /*
         * Marketing never reaches an address that opted out, whichever path
         * built the email. Logged once per key, so the admin sees why.
         */
        $suppressed = in_array($category, [EmailLog::CATEGORY_MARKETING, EmailLog::CATEGORY_CAMPAIGN], true)
            && EmailSuppression::isSuppressed($recipient, EmailSuppression::SCOPE_MARKETING);

        try {
            $log = EmailLog::create([
                'type'         => $type,
                'category'     => $category,
                'mailable'     => $mailable::class,
                'recipient'    => $recipient,
                'subject'      => $this->subject($mailable),
                'status'       => $suppressed ? EmailLog::STATUS_SUPPRESSED : EmailLog::STATUS_QUEUED,
                'error'        => $suppressed ? 'Unsubscribed from marketing email' : null,
                'dedupe_key'   => $dedupeKey,
                'related_type' => $related?->getMorphClass(),
                'related_id'   => $related?->getKey(),
            ]);
        } catch (UniqueConstraintViolationException) {
            /* Lost a race with a concurrent send for the same key. */
            return false;
        }

        if ($suppressed) {
            return false;
        }

        if (property_exists($mailable, 'emailLogId')) {
            $mailable->emailLogId = $log->id;
        }

        $mailable->onQueue(config('notifications.queue', 'default'));

        /* A real queue waits for the commit; "sync" sends here, inside this try/catch. */
        if (config('queue.default') !== 'sync') {
            $mailable->afterCommit();
        }

        try {
            Mail::queue($mailable);
        } catch (Throwable $e) {
            $log->update(['status' => EmailLog::STATUS_FAILED, 'error' => mb_substr($e->getMessage(), 0, 1000)]);

            Log::error('Email could not be queued', ['type' => $type, 'error' => $e->getMessage()]);

            return false;
        }

        return true;
    }

    /**
     * Phone-only sign-ups get a placeholder "{phone}@noreply.{host}" address.
     * Mail to it bounces, and bounces are what get a sender blocked.
     */
    public static function deliverable(?string $email): bool
    {
        return $email
            && filter_var($email, FILTER_VALIDATE_EMAIL)
            && ! str_contains(mb_strtolower($email), '@noreply.');
    }

    public function alreadySent(string $dedupeKey): bool
    {
        /* A failed attempt must not block a later one: park its key (see EmailLog::PARKED_KEY). */
        EmailLog::where('dedupe_key', $dedupeKey)
            ->where('status', EmailLog::STATUS_FAILED)
            ->update(['dedupe_key' => DB::raw("CONCAT(dedupe_key, '".EmailLog::PARKED_KEY."', id)")]);

        return EmailLog::where('dedupe_key', $dedupeKey)->exists();
    }

    protected function recipient(Mailable $mailable): ?string
    {
        $to = method_exists($mailable, 'envelope') ? ($mailable->envelope()->to[0] ?? null) : ($mailable->to[0] ?? null);

        $address = is_array($to) ? ($to['address'] ?? null) : $to?->address;

        return $address ? mb_strtolower($address) : null;
    }

    protected function subject(Mailable $mailable): ?string
    {
        return method_exists($mailable, 'envelope') ? $mailable->envelope()->subject : $mailable->subject;
    }
}

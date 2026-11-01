<?php

namespace Gabha\Notifications\Mail\Concerns;

/**
 * Carries the email_logs row id through the queue in a header, so the send
 * and failure listeners can update the row the dispatcher created.
 *
 * Retry policy: SMTP trouble (rate limits, a busy relay) passes in minutes,
 * so a failed send waits 1, 5, then 15 minutes before it is given up.
 */
trait TracksDelivery
{
    public ?int $emailLogId = null;

    public $tries = 4;

    public $backoff = [60, 300, 900];

    /**
     * @return array<string, string>
     */
    protected function trackingHeaders(): array
    {
        return $this->emailLogId ? ['X-UF-Log-Id' => (string) $this->emailLogId] : [];
    }
}

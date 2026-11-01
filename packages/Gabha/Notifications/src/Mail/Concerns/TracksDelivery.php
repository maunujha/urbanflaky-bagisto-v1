<?php

namespace Gabha\Notifications\Mail\Concerns;

/**
 * Carries the email_logs row id through the queue in a header, so the send
 * and failure listeners can update the row the dispatcher created.
 */
trait TracksDelivery
{
    public ?int $emailLogId = null;

    /**
     * @return array<string, string>
     */
    protected function trackingHeaders(): array
    {
        return $this->emailLogId ? ['X-UF-Log-Id' => (string) $this->emailLogId] : [];
    }
}

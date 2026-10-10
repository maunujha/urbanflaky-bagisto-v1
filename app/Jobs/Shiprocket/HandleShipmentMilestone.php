<?php

namespace App\Jobs\Shiprocket;

use App\Services\Shiprocket\OrderFulfilmentSync;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\SyncJob;
use Throwable;

/**
 * Applies a shipment stage change to the Bagisto order (shipment record,
 * shipped email, completion). Queued so a webhook answers Shiprocket fast
 * and an email or invoice failure retries on its own.
 */
class HandleShipmentMilestone implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $backoff = 120;

    public function __construct(
        public int $orderId,
        public ?string $from,
        public string $to,
    ) {}

    public function handle(OrderFulfilmentSync $sync): void
    {
        try {
            $sync->handle($this->orderId, $this->from, $this->to);
        } catch (Throwable $e) {
            /* On the sync queue (local dev) a throw would fail the webhook
               request itself; the failure is already logged. */
            if ($this->job instanceof SyncJob) {
                return;
            }

            throw $e;
        }
    }
}

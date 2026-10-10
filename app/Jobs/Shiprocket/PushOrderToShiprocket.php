<?php

namespace App\Jobs\Shiprocket;

use App\Models\ShiprocketOrder;
use App\Services\Shiprocket\ShipmentService;
use App\Services\Shiprocket\ShipmentStage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\SyncJob;
use Throwable;
use Webkul\Sales\Models\Order;

/**
 * Sends one order to Shiprocket. Runs off the checkout request, so a
 * Shiprocket outage never blocks an order; failures retry with backoff and
 * end as a visible "push failed" record the admin can retry by hand.
 */
class PushOrderToShiprocket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;

    public function __construct(public int $orderId) {}

    /** 1 min, 5 min, 15 min, 1 h. */
    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    public function handle(ShipmentService $service): void
    {
        $order = Order::find($this->orderId);

        if (! $order || ! $service->shouldPush($order)) {
            return;
        }

        try {
            $service->push($order);
        } catch (Throwable $e) {
            $service->recordError(ShiprocketOrder::firstOrCreate(['order_id' => $this->orderId]), $e);

            /* On the sync queue (local dev) a throw would surface inside checkout:
               record it and let shiprocket:sync or the admin retry instead. */
            if ($this->job instanceof SyncJob) {
                $this->failed($e);

                return;
            }

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        $shipment = ShiprocketOrder::firstOrCreate(['order_id' => $this->orderId]);

        if (! $shipment->isPushed()) {
            $shipment->forceFill(['status' => ShipmentStage::PUSH_FAILED])->save();
        }
    }
}

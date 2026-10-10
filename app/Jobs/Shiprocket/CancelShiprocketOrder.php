<?php

namespace App\Jobs\Shiprocket;

use App\Models\ShiprocketOrder;
use App\Services\Shiprocket\ShipmentService;
use DomainException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/**
 * A Bagisto cancellation, mirrored to Shiprocket while the parcel is still
 * at the warehouse.
 */
class CancelShiprocketOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $backoff = 300;

    public function __construct(public int $orderId) {}

    public function handle(ShipmentService $service): void
    {
        $shipment = ShiprocketOrder::where('order_id', $this->orderId)->first();

        if (! $shipment) {
            return;
        }

        try {
            $service->cancel($shipment, 'Cancelled from the store');
        } catch (DomainException $e) {
            /* Already picked up: nothing to cancel, make it visible instead. */
            $service->recordError($shipment, $e);
        } catch (Throwable $e) {
            $service->recordError($shipment, $e);

            throw $e;
        }
    }
}

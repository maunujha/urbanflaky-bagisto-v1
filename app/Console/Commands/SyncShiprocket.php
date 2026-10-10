<?php

namespace App\Console\Commands;

use App\Jobs\Shiprocket\PushOrderToShiprocket;
use App\Models\ShiprocketOrder;
use App\Services\Shiprocket\ShipmentService;
use App\Services\Shiprocket\ShipmentStage;
use App\Services\Shiprocket\ShiprocketException;
use App\Services\Shiprocket\ShiprocketSettings;
use App\Services\Shiprocket\TrackingService;
use Illuminate\Console\Command;
use Webkul\Sales\Models\Order;

/**
 * The safety net under the webhook and the push job. Every 30 minutes:
 *  1. queue a push for recent orders that never reached Shiprocket;
 *  2. assign a courier where automatic assignment failed earlier;
 *  3. poll tracking for active shipments whose webhook has gone quiet.
 */
class SyncShiprocket extends Command
{
    protected $signature = 'shiprocket:sync
        {--order= : Only this order increment id}
        {--dry-run : Report what would be done}';

    protected $description = 'Push missed orders, assign couriers and poll tracking for active Shiprocket shipments';

    /** Stop polling after this many consecutive API failures. */
    private const MAX_FAILURES = 3;

    public function handle(ShipmentService $shipments, TrackingService $tracking, ShiprocketSettings $settings): int
    {
        if ($settings->autoPush()) {
            $this->pushMissed();
        }

        if ($settings->autoAssignCourier()) {
            $this->assignMissing($shipments);
        }

        $this->pollTracking($tracking);

        return self::SUCCESS;
    }

    protected function pushMissed(): void
    {
        $orders = Order::query()
            ->whereNotIn('status', [Order::STATUS_CANCELED, Order::STATUS_CLOSED, Order::STATUS_PENDING_PAYMENT, Order::STATUS_COMPLETED])
            ->where('created_at', '>=', now()->subDays(7))
            ->where('created_at', '<=', now()->subMinutes(15))
            ->whereNotExists(fn ($q) => $q->from('shiprocket_orders')
                ->whereColumn('shiprocket_orders.order_id', 'orders.id')
                ->whereNotNull('shiprocket_order_id'))
            ->when($this->option('order'), fn ($q, $id) => $q->where('increment_id', $id))
            ->get()
            ->filter(fn (Order $order) => $order->haveStockableItems());

        foreach ($orders as $order) {
            $this->line("Order #{$order->increment_id}: queueing push");

            if (! $this->option('dry-run')) {
                PushOrderToShiprocket::dispatch($order->id);
            }
        }
    }

    protected function assignMissing(ShipmentService $shipments): void
    {
        $pending = $this->scope()
            ->whereNotNull('shiprocket_order_id')
            ->whereNull('awb_code')
            ->where('status', ShipmentStage::NEW)
            /* Recent pushes only: never book couriers for stale or legacy test orders. */
            ->where('pushed_at', '>=', now()->subDays(3))
            ->get();

        foreach ($pending as $shipment) {
            $this->line("Order id {$shipment->order_id}: assigning courier");

            if ($this->option('dry-run')) {
                continue;
            }

            try {
                $shipments->assignCourier($shipment);
            } catch (\Throwable $e) {
                $shipments->recordError($shipment, $e);
                $this->warn("  {$e->getMessage()}");
            }
        }
    }

    protected function pollTracking(TrackingService $tracking): void
    {
        $stale = now()->subMinutes((int) config('shiprocket.sync.stale_minutes'));

        $active = $this->scope()
            ->whereNotNull('awb_code')
            ->whereNotIn('status', ShipmentStage::TERMINAL)
            ->where(fn ($q) => $q->whereNull('last_event_at')->orWhere('last_event_at', '<', $stale))
            ->where(fn ($q) => $q->whereNull('last_synced_at')->orWhere('last_synced_at', '<', $stale))
            ->orderBy('last_synced_at')
            ->limit((int) config('shiprocket.sync.batch'))
            ->get();

        $failures = 0;

        foreach ($active as $shipment) {
            if ($this->option('dry-run')) {
                $this->line("AWB {$shipment->awb_code}: would poll");

                continue;
            }

            try {
                $tracking->refresh($shipment);
                $failures = 0;

                $this->line("AWB {$shipment->awb_code}: {$shipment->refresh()->status}");
            } catch (ShiprocketException $e) {
                $this->warn("AWB {$shipment->awb_code}: {$e->getMessage()}");

                if (++$failures >= self::MAX_FAILURES) {
                    $this->error('Shiprocket keeps failing; stopping this run.');

                    return;
                }
            }
        }
    }

    protected function scope()
    {
        return ShiprocketOrder::query()
            ->when($this->option('order'), fn ($q, $id) => $q->whereHas('order', fn ($o) => $o->where('increment_id', $id)));
    }
}

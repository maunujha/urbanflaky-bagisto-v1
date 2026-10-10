<?php

namespace App\Services\Shiprocket;

use App\Models\ShiprocketOrder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\ShipmentRepository;

/**
 * Mirrors courier milestones onto the Bagisto order, so admin, customer
 * account and emails all tell the same story as the courier.
 */
class OrderFulfilmentSync
{
    public function __construct(
        protected ShipmentRepository $shipmentRepository,
        protected InvoiceRepository $invoiceRepository,
        protected OrderRepository $orderRepository,
    ) {}

    public function handle(int $orderId, ?string $from, string $to): void
    {
        $order    = Order::find($orderId);
        $shipment = ShiprocketOrder::where('order_id', $orderId)->first();

        if (! $order || ! $shipment) {
            return;
        }

        $failure = null;

        if (in_array($to, ShipmentStage::IN_CARRIER_HANDS, true)) {
            $failure = $this->attempt(fn () => $this->ship($order, $shipment), $order, 'create shipment');
        }

        if ($to === ShipmentStage::DELIVERED) {
            $failure = $this->attempt(fn () => $this->complete($order->refresh()), $order, 'complete order') ?? $failure;

            /* RewardCoins opens the return window from here; its listeners are idempotent. */
            Event::dispatch('shiprocket.order.delivered', $order);
        }

        if ($to === ShipmentStage::RTO_DELIVERED) {
            Log::warning('Shiprocket RTO delivered: parcel is back at the warehouse; restock and refund/cancel from admin', [
                'order' => $order->increment_id,
            ]);
        }

        /* Surface the failure so a real queue retries; every step is idempotent. */
        if ($failure) {
            throw $failure;
        }
    }

    protected function attempt(callable $step, Order $order, string $what): ?Throwable
    {
        try {
            $step();

            return null;
        } catch (Throwable $e) {
            Log::error("Shiprocket milestone: could not {$what}", ['order' => $order->increment_id, 'error' => $e->getMessage()]);

            return $e;
        }
    }

    /**
     * Create the Bagisto shipment (carrier + AWB) for everything still
     * unshipped. Fires the "your order has shipped" email and moves the order
     * to Shipped. Skipped when admin already shipped it by hand.
     */
    protected function ship(Order $order, ShiprocketOrder $shipment): void
    {
        if (! $order->canShip() || ! $order->shipping_address) {
            return;
        }

        $sourceId = $order->channel?->inventory_sources()->value('inventory_sources.id')
            ?? InventorySource::query()->value('id');

        $items = [];

        foreach ($order->items as $item) {
            if ($item->qty_to_ship > 0) {
                $items[$item->id] = [$sourceId => $item->qty_to_ship];
            }
        }

        if (! $sourceId || ! $items) {
            return;
        }

        $this->shipmentRepository->create([
            'order_id' => $order->id,
            'shipment' => [
                'carrier_title' => $shipment->courier_name ?: 'Shiprocket',
                'track_number'  => (string) $shipment->awb_code,
                'source'        => $sourceId,
                'items'         => $items,
            ],
        ]);

        Log::info('Bagisto shipment created from Shiprocket', ['order' => $order->increment_id, 'awb' => $shipment->awb_code]);
    }

    /**
     * Delivered means paid for COD: invoice what is still uninvoiced, then
     * mark the order Completed.
     */
    protected function complete(Order $order): void
    {
        if (in_array($order->status, [Order::STATUS_CANCELED, Order::STATUS_CLOSED, Order::STATUS_COMPLETED], true)) {
            return;
        }

        if ($order->payment?->method === 'cashondelivery' && $order->canInvoice()) {
            $items = [];

            foreach ($order->items as $item) {
                if ($item->qty_to_invoice > 0) {
                    $items[$item->id] = $item->qty_to_invoice;
                }
            }

            if ($items) {
                $this->invoiceRepository->create([
                    'order_id' => $order->id,
                    'invoice'  => ['items' => $items],
                ], 'paid');

                $order->refresh();
            }
        }

        $this->orderRepository->updateOrderStatus($order, Order::STATUS_COMPLETED);
    }
}

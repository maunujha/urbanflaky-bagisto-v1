<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository;

class MarkOrderShipped
{
    public function __construct(protected OrderRepository $orderRepository) {}

    /**
     * Fires on sales.shipment.save.after.
     *
     * Bagisto already called updateOrderStatus() and may have set the order to
     * "completed" (all invoiced + all shipped). We intercept here and hold it at
     * "shipped" until the delivery webhook arrives from Shiprocket.
     */
    public function handle($shipment): void
    {
        $order = $shipment->order;

        if (! $order) {
            return;
        }

        // Only override if Bagisto just promoted it to "completed" or "processing"
        // (i.e. a physical shipment was just created — not a refund/cancel flow)
        if (! in_array($order->status, [Order::STATUS_COMPLETED, Order::STATUS_PROCESSING])) {
            return;
        }

        // Check all ordered items are shipped (Bagisto considers it "completed")
        $allShipped = $order->items->every(
            fn ($item) => $item->qty_ordered <= ($item->qty_shipped + $item->qty_canceled)
        );

        if (! $allShipped) {
            return;
        }

        $order->status = Order::STATUS_SHIPPED;
        $order->save();

        Log::info('Order marked as shipped', ['order' => $order->increment_id]);
    }
}

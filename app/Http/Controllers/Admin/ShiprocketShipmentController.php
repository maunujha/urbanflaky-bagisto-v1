<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShiprocketOrder;
use App\Services\Shiprocket\ServiceabilityService;
use App\Services\Shiprocket\ShipmentService;
use App\Services\Shiprocket\ShiprocketException;
use App\Services\Shiprocket\TrackingService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Sales\Models\Order;

/**
 * The Shiprocket panel on the admin order page: push, courier choice,
 * pickup, documents, refresh and cancel. Every action reports back as a
 * flash message; failures are also stored on the shipment record.
 */
class ShiprocketShipmentController extends Controller
{
    public function __construct(
        protected ShipmentService $shipments,
        protected TrackingService $tracking,
        protected ServiceabilityService $serviceability,
    ) {}

    public function push(int $orderId): RedirectResponse
    {
        $order = $this->authorizeOrder($orderId);

        if (! $this->shipments->shouldPush($order)) {
            return $this->back($orderId, 'error', 'This order has nothing to ship, or is cancelled or awaiting payment.');
        }

        return $this->run($orderId, fn () => $this->shipments->push($order), 'Order sent to Shiprocket.');
    }

    public function couriers(int $orderId): View|RedirectResponse
    {
        $order    = $this->authorizeOrder($orderId);
        $shipment = $this->shipment($orderId);

        if (! $shipment?->isPushed()) {
            return $this->back($orderId, 'error', 'Send the order to Shiprocket first.');
        }

        try {
            $couriers = $this->serviceability->forShiprocketOrder($shipment->shiprocket_order_id);
        } catch (ShiprocketException $e) {
            return $this->back($orderId, 'error', $e->getMessage());
        }

        return view('shiprocket::admin.couriers', compact('order', 'shipment', 'couriers'));
    }

    public function assign(Request $request, int $orderId): RedirectResponse
    {
        $this->authorizeOrder($orderId);

        $courierId = $request->validate(['courier_id' => 'nullable|string|max:20'])['courier_id'] ?? null;

        return $this->run(
            $orderId,
            fn () => $this->shipments->assignCourier($this->shipmentOrFail($orderId), $courierId),
            $courierId ? 'Courier changed.' : 'Recommended courier assigned.',
        );
    }

    public function pickup(int $orderId): RedirectResponse
    {
        $this->authorizeOrder($orderId);

        return $this->run($orderId, fn () => $this->shipments->schedulePickup($this->shipmentOrFail($orderId)), 'Pickup booked.');
    }

    public function refresh(int $orderId): RedirectResponse
    {
        $this->authorizeOrder($orderId);

        return $this->run($orderId, fn () => $this->tracking->refresh($this->shipmentOrFail($orderId)), 'Tracking refreshed.');
    }

    public function cancel(int $orderId): RedirectResponse
    {
        $this->authorizeOrder($orderId);

        return $this->run($orderId, fn () => $this->shipments->cancel($this->shipmentOrFail($orderId), 'Cancelled in Shiprocket by admin'), 'Shipment cancelled in Shiprocket.');
    }

    public function document(int $orderId, string $type): RedirectResponse
    {
        $this->authorizeOrder($orderId);

        try {
            return redirect()->away($this->shipments->document($this->shipmentOrFail($orderId), $type));
        } catch (DomainException|ShiprocketException $e) {
            return $this->back($orderId, 'error', $e->getMessage());
        }
    }

    protected function run(int $orderId, callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $e) {
            return $this->back($orderId, 'error', $e->getMessage());
        } catch (ShiprocketException $e) {
            if ($shipment = $this->shipment($orderId)) {
                $this->shipments->recordError($shipment, $e);
            }

            return $this->back($orderId, 'error', $e->getMessage());
        }

        return $this->back($orderId, 'success', $success);
    }

    protected function authorizeOrder(int $orderId): Order
    {
        abort_unless(bouncer()->hasPermission('sales.orders.shiprocket'), 403);

        return Order::findOrFail($orderId);
    }

    protected function shipment(int $orderId): ?ShiprocketOrder
    {
        return ShiprocketOrder::where('order_id', $orderId)->first();
    }

    protected function shipmentOrFail(int $orderId): ShiprocketOrder
    {
        return $this->shipment($orderId) ?? throw new DomainException('This order has not been sent to Shiprocket yet.');
    }

    protected function back(int $orderId, string $type, string $message): RedirectResponse
    {
        session()->flash($type, $message);

        return redirect()->route('admin.sales.orders.view', $orderId);
    }
}

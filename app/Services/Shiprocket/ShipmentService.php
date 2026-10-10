<?php

namespace App\Services\Shiprocket;

use App\Models\ShiprocketOrder;
use DomainException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Sales\Models\Order;

/**
 * Every action the store takes on a Shiprocket shipment. Each method is safe
 * to call twice: it reads the stored record first and skips what is done.
 */
class ShipmentService
{
    public function __construct(
        protected ShiprocketClient $client,
        protected OrderPayloadBuilder $payloadBuilder,
        protected TrackingService $tracking,
        protected ShiprocketSettings $settings,
    ) {}

    /**
     * Whether an order should go to Shiprocket at all.
     */
    public function shouldPush(Order $order): bool
    {
        return $order->haveStockableItems()
            && ! in_array($order->status, [Order::STATUS_CANCELED, Order::STATUS_CLOSED, Order::STATUS_PENDING_PAYMENT], true);
    }

    /**
     * Create the Shiprocket order (once), then assign a courier and book the
     * pickup if automation is on. Throws on a failed create so the queue
     * retries; courier/pickup failures are recorded, not thrown, because the
     * order itself is safely in Shiprocket and the poller or admin can finish.
     */
    public function push(Order $order): ShiprocketOrder
    {
        $lock = Cache::lock("shiprocket:push:{$order->id}", 120);

        if (! $lock->get()) {
            throw new ShiprocketException("Order {$order->increment_id} is already being pushed.");
        }

        try {
            $shipment = ShiprocketOrder::firstOrCreate(['order_id' => $order->id]);

            if (! $shipment->isPushed()) {
                $this->createRemoteOrder($order, $shipment);
            }

            if ($this->settings->autoAssignCourier() && ! $shipment->hasAwb()) {
                $this->attempt($shipment, fn () => $this->assignCourier($shipment));
            }

            if ($this->settings->autoSchedulePickup() && $shipment->hasAwb() && ! $shipment->pickup_scheduled_at) {
                $this->attempt($shipment, fn () => $this->schedulePickup($shipment));
            }

            return $shipment->refresh();
        } finally {
            $lock->release();
        }
    }

    /**
     * Assign a courier. Without $courierId Shiprocket applies the account's
     * courier priority (set it to "Recommended" in Shiprocket → Settings).
     * With one, an existing AWB is released first so the parcel is re-booked.
     */
    public function assignCourier(ShiprocketOrder $shipment, ?string $courierId = null): ShiprocketOrder
    {
        if (! $shipment->isPushed()) {
            throw new DomainException('Push the order to Shiprocket before assigning a courier.');
        }

        if ($shipment->hasAwb()) {
            if (! $courierId) {
                return $shipment;
            }

            if (! $shipment->isBeforePickup()) {
                throw new DomainException('The parcel is already with the courier and can no longer be reassigned.');
            }

            $this->client->cancelAwbs([$shipment->awb_code]);

            $shipment->forceFill([
                'awb_code'            => null,
                'courier_name'        => null,
                'courier_company_id'  => null,
                'pickup_scheduled_at' => null,
                'label_url'           => null,
                'manifest_url'        => null,
                'status'              => ShipmentStage::NEW,
            ])->save();
        }

        $response = $this->client->assignAwb($shipment->shipment_id, $courierId);
        $data     = $response['response']['data'] ?? [];

        if ((int) ($response['awb_assign_status'] ?? 0) !== 1 || empty($data['awb_code'])) {
            throw new ShiprocketException('Courier not assigned: '.($data['awb_assign_error'] ?? $response['message'] ?? 'no AWB returned'));
        }

        $this->tracking->transition($shipment, ShipmentStage::AWB_ASSIGNED, 'Courier assigned: '.($data['courier_name'] ?? ''), [
            'awb_code'           => (string) $data['awb_code'],
            'courier_name'       => $data['courier_name'] ?? null,
            'courier_company_id' => isset($data['courier_company_id']) ? (string) $data['courier_company_id'] : null,
            'awb_assigned_at'    => now(),
            'last_error'         => null,
            'last_error_at'      => null,
        ]);

        return $shipment;
    }

    public function schedulePickup(ShiprocketOrder $shipment): ShiprocketOrder
    {
        if (! $shipment->hasAwb()) {
            throw new DomainException('Assign a courier before booking the pickup.');
        }

        try {
            $response = $this->client->generatePickup($shipment->shipment_id);
        } catch (ShiprocketException $e) {
            /* Booking twice is an error on Shiprocket's side but success on ours. */
            if (! str_contains(strtolower($e->getMessage()), 'already')) {
                throw $e;
            }

            $response = [];
        }

        $date = $response['response']['pickup_scheduled_date'] ?? null;

        $this->tracking->transition($shipment, ShipmentStage::PICKUP_SCHEDULED, 'Pickup booked'.($date ? ' for '.$date : ''), [
            'pickup_scheduled_at' => now(),
        ]);

        return $shipment;
    }

    /**
     * Shipping label, manifest or Shiprocket invoice PDF. URLs are cached on
     * the record; a courier change clears them.
     */
    public function document(ShiprocketOrder $shipment, string $type): string
    {
        $column = $type.'_url';

        if (! in_array($type, ['label', 'manifest', 'invoice'], true)) {
            throw new DomainException("Unknown document type {$type}.");
        }

        if ($shipment->{$column}) {
            return $shipment->{$column};
        }

        if ($type !== 'invoice' && ! $shipment->hasAwb()) {
            throw new DomainException('Assign a courier first; the label and manifest need an AWB.');
        }

        $url = match ($type) {
            'label'    => $this->client->generateLabel($shipment->shipment_id)['label_url'] ?? null,
            'invoice'  => $this->client->printInvoice($shipment->shiprocket_order_id)['invoice_url'] ?? null,
            'manifest' => $this->manifestUrl($shipment),
        };

        if (! $url) {
            throw new ShiprocketException("Shiprocket returned no {$type} URL.");
        }

        $shipment->forceFill([$column => $url])->save();

        return $url;
    }

    /**
     * Cancel in Shiprocket. Only possible before the courier has the parcel.
     */
    public function cancel(ShiprocketOrder $shipment, string $reason = 'Order cancelled'): void
    {
        if ($shipment->status === ShipmentStage::CANCELED) {
            return;
        }

        if (! $shipment->isBeforePickup()) {
            throw new DomainException('The parcel has been picked up; it can only come back as a return.');
        }

        if ($shipment->isPushed()) {
            $this->client->cancelOrders([$shipment->shiprocket_order_id]);
        }

        $this->tracking->transition($shipment, ShipmentStage::CANCELED, $reason);
    }

    public function recordError(ShiprocketOrder $shipment, Throwable $e): void
    {
        $shipment->forceFill([
            'last_error'    => mb_substr($e->getMessage(), 0, 2000),
            'last_error_at' => now(),
        ])->save();

        Log::warning('Shiprocket action failed', [
            'order_id' => $shipment->order_id,
            'error'    => $e->getMessage(),
        ]);
    }

    protected function createRemoteOrder(Order $order, ShiprocketOrder $shipment): void
    {
        $shipment->increment('push_attempts');

        /* A retry after a timeout may find the order already created. */
        $remote = $shipment->push_attempts > 1 ? $this->findRemoteOrder($order) : null;

        $remote ??= $this->client->createOrder($this->payloadBuilder->build($order));

        $remoteId   = (string) ($remote['order_id'] ?? '');
        $shipmentId = (string) ($remote['shipment_id'] ?? '');

        if ($remoteId === '' || $shipmentId === '') {
            throw new ShiprocketException('Shiprocket accepted the order but returned no order or shipment id: '.json_encode($remote));
        }

        $this->tracking->transition($shipment, ShipmentStage::NEW, 'Order received by Shiprocket', array_filter([
            'shiprocket_order_id' => $remoteId,
            'shipment_id'         => $shipmentId,
            'awb_code'            => ($remote['awb_code'] ?? '') ?: null,
            'courier_name'        => ($remote['courier_name'] ?? '') ?: null,
            'is_cod'              => $this->payloadBuilder->isCod($order),
            'pushed_at'           => now(),
        ], fn ($v) => $v !== null) + ['last_error' => null, 'last_error_at' => null]);

        Log::info('Shiprocket order created', [
            'order'               => $order->increment_id,
            'shiprocket_order_id' => $remoteId,
            'shipment_id'         => $shipmentId,
        ]);
    }

    /**
     * @return array{order_id: string, shipment_id: string, awb_code: ?string}|null
     */
    protected function findRemoteOrder(Order $order): ?array
    {
        $match = collect($this->client->searchOrders((string) $order->increment_id)['data'] ?? [])
            ->first(fn ($o) => (string) ($o['channel_order_id'] ?? '') === (string) $order->increment_id);

        if (! $match) {
            return null;
        }

        return [
            'order_id'    => (string) $match['id'],
            'shipment_id' => (string) ($match['shipments'][0]['id'] ?? ''),
            'awb_code'    => $match['shipments'][0]['awb'] ?? null,
        ];
    }

    protected function manifestUrl(ShiprocketOrder $shipment): ?string
    {
        try {
            return $this->client->generateManifest($shipment->shipment_id)['manifest_url'] ?? null;
        } catch (ShiprocketException) {
            /* Already generated: print the existing one. */
            return $this->client->printManifest($shipment->shiprocket_order_id)['manifest_url'] ?? null;
        }
    }

    protected function attempt(ShiprocketOrder $shipment, callable $action): void
    {
        try {
            $action();
        } catch (Throwable $e) {
            $this->recordError($shipment, $e);
        }
    }
}

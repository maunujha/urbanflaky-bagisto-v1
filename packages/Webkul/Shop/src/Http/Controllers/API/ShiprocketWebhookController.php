<?php

namespace Webkul\Shop\Http\Controllers\API;

use App\Models\ShiprocketOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Shop\Http\Controllers\Controller;

class ShiprocketWebhookController extends Controller
{
    /**
     * Shiprocket status strings that mean the shipment was cancelled.
     */
    const CANCELED_STATUSES = [
        'CANCELED',
        'Cancelled',
        'CANCELLED',
        'RTO',
        'RTO Initiated',
        'RTO Delivered',
        'Lost',
        'LOST',
        'Damaged',
    ];

    /**
     * Shiprocket status strings that mean the order was delivered.
     */
    const DELIVERED_STATUSES = [
        'Delivered',
        'DELIVERED',
    ];

    public function __construct(protected OrderRepository $orderRepository) {}

    /**
     * Handle incoming Shiprocket webhook.
     *
     * Shiprocket sends POST with JSON body. Token is passed as a query param:
     *   POST /api/webhooks/shiprocket?token=<SHIPROCKET_WEBHOOK_TOKEN>
     */
    public function handle(Request $request)
    {
        if (! $this->isAuthorized($request)) {
            Log::warning('Shiprocket webhook: unauthorized request', [
                'ip'    => $request->ip(),
                'token' => $request->query('token'),
            ]);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        Log::info('Shiprocket webhook received', $payload);

        $channelOrderId = $payload['channel_order_id']  // Bagisto increment_id
            ?? $payload['order_id']
            ?? null;

        $awb            = $payload['awb']            ?? null;
        $currentStatus  = $payload['current_status'] ?? null;
        $shipmentId     = (string) ($payload['shipment_id'] ?? '');

        if (! $channelOrderId || ! $currentStatus) {
            return response()->json(['message' => 'Missing fields'], 422);
        }

        // Look up the Bagisto order by increment_id
        $order = $this->orderRepository->findOneWhere(['increment_id' => $channelOrderId]);

        if (! $order) {
            Log::warning('Shiprocket webhook: order not found', ['channel_order_id' => $channelOrderId]);

            return response()->json(['message' => 'Order not found'], 404);
        }

        // Keep ShiprocketOrder model in sync
        ShiprocketOrder::where('order_id', $order->id)->update([
            'status'   => strtolower($currentStatus),
            'awb_code' => $awb ?: null,
        ]);

        if (in_array($currentStatus, self::CANCELED_STATUSES)) {
            $this->handleCancellation($order, $currentStatus);
        } elseif (in_array($currentStatus, self::DELIVERED_STATUSES)) {
            $this->handleDelivery($order, $currentStatus);
        } else {
            Log::info('Shiprocket webhook: no action for status', [
                'order'  => $channelOrderId,
                'status' => $currentStatus,
            ]);
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Cancel the Bagisto order if it can still be cancelled.
     */
    protected function handleCancellation($order, string $status): void
    {
        if (! $order->canCancel()) {
            Log::info('Shiprocket webhook: order cannot be cancelled', [
                'order'  => $order->increment_id,
                'status' => $order->status,
            ]);

            return;
        }

        $this->orderRepository->cancel($order->id);

        Log::info('Shiprocket webhook: order cancelled', [
            'order'             => $order->increment_id,
            'shiprocket_status' => $status,
        ]);
    }

    /**
     * Mark the Bagisto order as completed when Shiprocket confirms delivery.
     */
    protected function handleDelivery($order, string $status): void
    {
        $order->status = \Webkul\Sales\Models\Order::STATUS_COMPLETED;
        $order->save();

        Log::info('Shiprocket webhook: order marked completed on delivery', [
            'order'             => $order->increment_id,
            'shiprocket_status' => $status,
        ]);
    }

    /**
     * Verify the request token matches our stored webhook token.
     */
    protected function isAuthorized(Request $request): bool
    {
        $expected = config('shiprocket.webhook_token');

        if (! $expected) {
            return true; // token not configured, allow all (not recommended for production)
        }

        return $request->query('token') === $expected
            || $request->header('X-Shiprocket-Token') === $expected;
    }
}

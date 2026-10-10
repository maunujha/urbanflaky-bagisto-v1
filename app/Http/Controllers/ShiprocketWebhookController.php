<?php

namespace App\Http\Controllers;

use App\Models\ShiprocketOrder;
use App\Services\Shiprocket\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Models\Order;

/**
 * Shiprocket tracking webhook (Shiprocket → Settings → API → Webhooks).
 *
 * Configure it with URL https://urbanflaky.in/webhooks/tracking (Shiprocket
 * rejects URLs containing "shiprocket") and the SHIPROCKET_WEBHOOK_TOKEN as
 * the x-api-key. Every authenticated call is answered 200, even for unknown
 * orders, so Shiprocket never disables the hook.
 */
class ShiprocketWebhookController extends Controller
{
    public function __construct(protected TrackingService $tracking) {}

    public function handle(Request $request): JsonResponse
    {
        /*
         * Fail closed: with no token configured the endpoint rejects everything,
         * otherwise a missing env var would open this CSRF-exempt route to anyone.
         */
        $expectedToken = config('shiprocket.webhook_token');

        if (! $expectedToken || ! hash_equals($expectedToken, (string) $request->header('x-api-key'))) {
            Log::warning('Shiprocket webhook: invalid token');

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        $awb    = trim((string) ($payload['awb'] ?? ''));
        $status = (string) ($payload['current_status'] ?? $payload['shipment_status'] ?? '');

        $shipment = $this->findShipment($payload, $awb);

        Log::info('Shiprocket webhook received', [
            'awb'      => $awb,
            'status'   => $status,
            'order'    => $shipment?->order?->increment_id,
        ]);

        if (! $shipment) {
            return response()->json(['message' => 'ok']);
        }

        $this->tracking->applyCourierUpdate($shipment, [
            'status'  => $status,
            'awb'     => $awb ?: null,
            'courier' => $payload['courier_name'] ?? null,
            'etd'     => $payload['etd'] ?? null,
            'scans'   => array_map(fn ($scan) => [
                'date'     => $scan['date'] ?? null,
                'status'   => $scan['sr-status-label'] ?? ($scan['status'] ?? null),
                'activity' => $scan['activity'] ?? null,
                'location' => $scan['location'] ?? null,
            ], is_array($payload['scans'] ?? null) ? $payload['scans'] : []),
        ], 'webhook');

        return response()->json(['message' => 'ok']);
    }

    /**
     * AWB first (unique per parcel), then Shiprocket's order id, then our
     * order number, which Shiprocket echoes as order_id / channel_order_id.
     */
    protected function findShipment(array $payload, string $awb): ?ShiprocketOrder
    {
        if ($awb !== '' && $shipment = ShiprocketOrder::where('awb_code', $awb)->first()) {
            return $shipment;
        }

        if ($srOrderId = (string) ($payload['sr_order_id'] ?? '')) {
            if ($shipment = ShiprocketOrder::where('shiprocket_order_id', $srOrderId)->first()) {
                return $shipment;
            }
        }

        $incrementId = (string) ($payload['channel_order_id'] ?? $payload['order_id'] ?? '');

        if ($incrementId === '' || ! $order = Order::where('increment_id', $incrementId)->first()) {
            return null;
        }

        return ShiprocketOrder::where('order_id', $order->id)->first();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ShiprocketOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Models\Order;

class ShiprocketWebhookController extends Controller
{
    /**
     * Receive Shiprocket tracking webhook and update AWB / status.
     *
     * Shiprocket payload fields we use:
     *   channel_order_id  → our Bagisto increment_id
     *   order_id          → Shiprocket's internal order ID
     *   awb               → AWB tracking number (integer)
     *   courier_name      → courier company
     *   current_status    → latest status string
     */
    public function handle(Request $request)
    {
        /* Verify token sent in x-api-key header */
        $expectedToken = config('shiprocket.webhook_token');

        if ($expectedToken && $request->header('x-api-key') !== $expectedToken) {
            Log::warning('Shiprocket webhook: invalid token');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        Log::info('Shiprocket webhook received', $payload);

        /* channel_order_id is what we passed as order_id when creating the order */
        $channelOrderId = (string) ($payload['channel_order_id'] ?? '');
        $awb            = (string) ($payload['awb']            ?? '');
        $courier        = (string) ($payload['courier_name']   ?? '');
        $status         = (string) ($payload['current_status'] ?? $payload['shipment_status'] ?? '');
        $srOrderId      = (string) ($payload['order_id']       ?? '');

        /* Return 200 for Shiprocket's test webhook (fake channel_order_id) */
        if (! $channelOrderId || $channelOrderId === 'enter your channel order id') {
            return response()->json(['message' => 'ok']);
        }

        $order = Order::where('increment_id', $channelOrderId)->first();

        if (! $order) {
            Log::warning('Shiprocket webhook: order not found', ['channel_order_id' => $channelOrderId]);
            /* Still return 200 so Shiprocket does not keep retrying */
            return response()->json(['message' => 'ok']);
        }

        ShiprocketOrder::updateOrCreate(
            ['order_id' => $order->id],
            array_filter([
                'shiprocket_order_id' => $srOrderId  ?: null,
                'awb_code'            => $awb         ?: null,
                'courier_name'        => $courier      ?: null,
                'status'              => $status       ?: null,
            ])
        );

        Log::info('Shiprocket order updated via webhook', [
            'order'   => $channelOrderId,
            'awb'     => $awb,
            'status'  => $status,
            'courier' => $courier,
        ]);

        return response()->json(['message' => 'ok']);
    }
}

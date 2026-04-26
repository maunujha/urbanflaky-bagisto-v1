<?php

namespace App\Http\Controllers;

use App\Models\ShiprocketOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Models\Order;

class ShiprocketWebhookController extends Controller
{
    /**
     * Receive Shiprocket status/AWB webhook and update our records.
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('Shiprocket webhook received', $payload);

        $awb        = $payload['awb']       ?? $payload['awb_code']   ?? null;
        $courier    = $payload['courier']   ?? $payload['courier_name'] ?? null;
        $status     = $payload['status']    ?? null;
        $incrementId = (string) ($payload['order_id'] ?? '');

        if (! $incrementId) {
            return response()->json(['message' => 'order_id missing'], 400);
        }

        $order = Order::where('increment_id', $incrementId)->first();

        if (! $order) {
            Log::warning('Shiprocket webhook: order not found', ['increment_id' => $incrementId]);
            return response()->json(['message' => 'order not found'], 404);
        }

        ShiprocketOrder::updateOrCreate(
            ['order_id' => $order->id],
            array_filter([
                'awb_code'     => $awb,
                'courier_name' => $courier,
                'status'       => $status,
            ])
        );

        Log::info('Shiprocket order updated via webhook', [
            'order'   => $incrementId,
            'awb'     => $awb,
            'status'  => $status,
            'courier' => $courier,
        ]);

        return response()->json(['message' => 'ok']);
    }
}

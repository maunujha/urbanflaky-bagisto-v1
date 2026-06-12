<?php

namespace App\Http\Controllers;

use App\Models\ShiprocketOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrackOrderController extends Controller
{
    const AUTH_URL  = 'https://apiv2.shiprocket.in/v1/external/auth/login';
    const TRACK_URL = 'https://apiv2.shiprocket.in/v1/external/courier/track/awb/';

    /**
     * Render the public order-tracking page.
     */
    public function index()
    {
        return view('shop::track-order.index');
    }

    /**
     * Resolve an AWB (or one of our order IDs) and return live Shiprocket status.
     *
     * Accepts either:
     *   - a Shiprocket AWB / tracking number, or
     *   - one of our order increment_ids (mapped to its AWB via shiprocket_orders).
     */
    public function track(Request $request): JsonResponse
    {
        $request->validate([
            'awb' => 'required|string|max:40',
        ]);

        $input = trim($request->input('awb'));

        $awb = $this->resolveAwb($input);

        if (! $awb) {
            return response()->json([
                'found'   => false,
                'message' => "We couldn't find a shipment for \"{$input}\". Tracking becomes available 24–48 hours after dispatch. Please double-check your AWB or Order ID.",
            ]);
        }

        $token = $this->getToken();

        if (! $token) {
            return response()->json([
                'found'   => false,
                'message' => 'Tracking is temporarily unavailable. Please try again in a few minutes or contact our support team.',
            ], 503);
        }

        try {
            $response = Http::withToken($token)
                ->timeout(12)
                ->get(self::TRACK_URL . urlencode($awb));

            if ($response->status() === 401) {
                Cache::forget('shiprocket_token');
            }

            if ($response->successful()) {
                $data = $this->normalize($response->json('tracking_data') ?? [], $awb);

                if ($data) {
                    return response()->json($data);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Order tracking failed', ['awb' => $awb, 'error' => $e->getMessage()]);
        }

        return response()->json([
            'found'   => false,
            'message' => "No movement found for AWB {$awb} yet. If your order shipped recently, please allow 24–48 hours for the courier to update tracking.",
        ]);
    }

    /**
     * Turn whatever the customer typed into a real AWB code.
     */
    protected function resolveAwb(string $input): ?string
    {
        /* Maybe it's one of our order IDs → look up its stored AWB. */
        $srOrder = ShiprocketOrder::whereHas('order', fn ($q) => $q->where('increment_id', $input))
            ->whereNotNull('awb_code')
            ->where('awb_code', '!=', '')
            ->first();

        if ($srOrder?->awb_code) {
            return $srOrder->awb_code;
        }

        /* Otherwise treat the input itself as the AWB. */
        return $input !== '' ? $input : null;
    }

    /**
     * Reshape the Shiprocket tracking payload into the structure our view expects.
     */
    protected function normalize(array $tracking, string $awb): ?array
    {
        $track = $tracking['shipment_track'][0] ?? null;

        $activities = $tracking['shipment_track_activities'] ?? [];

        /* Shiprocket returns track_status = 0 with no track when the AWB is unknown. */
        if (! $track && empty($activities)) {
            return null;
        }

        $status = $track['current_status'] ?? ($activities[0]['activity'] ?? 'Pending');

        return [
            'found'          => true,
            'awb'            => $track['awb_code'] ?? $awb,
            'courier'        => $track['courier_name'] ?? ($tracking['courier_name'] ?? null),
            'current_status' => $status,
            'origin'         => $track['origin'] ?? null,
            'destination'    => $track['destination'] ?? null,
            'consignee'      => $this->maskName($track['consignee_name'] ?? null),
            'edd'            => $tracking['etd'] ?? ($track['edd'] ?? null),
            'delivered_date' => $track['delivered_date'] ?? null,
            'stage'          => $this->stage($status),
            'activities'     => array_map(fn ($a) => [
                'date'     => $a['date']     ?? null,
                'activity' => $a['activity'] ?? ($a['status'] ?? ''),
                'location' => $a['location'] ?? '',
            ], $activities),
        ];
    }

    /**
     * Partially mask a consignee name so this public, enumerable endpoint never
     * exposes a full name. "Rahul Sharma" becomes "R•••• S•••••".
     */
    protected function maskName(?string $name): ?string
    {
        if (! $name = trim((string) $name)) {
            return null;
        }

        return implode(' ', array_map(function ($word) {
            $first = mb_substr($word, 0, 1);

            return $first.str_repeat('•', max(0, mb_strlen($word) - 1));
        }, preg_split('/\s+/', $name)));
    }

    /**
     * Map a free-text courier status to one of five progress stages (0–4).
     */
    protected function stage(string $status): int
    {
        $s = strtolower($status);

        return match (true) {
            str_contains($s, 'deliver') && ! str_contains($s, 'out for') => 4,
            str_contains($s, 'out for delivery')                          => 3,
            str_contains($s, 'transit'), str_contains($s, 'dispatch'),
            str_contains($s, 'shipped'), str_contains($s, 'reached')      => 2,
            str_contains($s, 'picked'), str_contains($s, 'pickup'),
            str_contains($s, 'manifest')                                  => 1,
            default                                                       => 0,
        };
    }

    /**
     * Get or refresh the cached Shiprocket auth token.
     */
    protected function getToken(): ?string
    {
        return Cache::remember('shiprocket_token', 23 * 3600, function () {
            try {
                $response = Http::timeout(10)->post(self::AUTH_URL, [
                    'email'    => config('shiprocket.email'),
                    'password' => config('shiprocket.password'),
                ]);

                if ($response->successful() && $response->json('token')) {
                    return $response->json('token');
                }
            } catch (\Exception $e) {
                Log::error('Shiprocket auth failed', ['message' => $e->getMessage()]);
            }

            return null;
        });
    }
}

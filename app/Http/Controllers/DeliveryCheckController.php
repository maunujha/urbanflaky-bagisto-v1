<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeliveryCheckController extends Controller
{
    const AUTH_URL           = 'https://apiv2.shiprocket.in/v1/external/auth/login';
    const SERVICEABILITY_URL = 'https://apiv2.shiprocket.in/v1/external/courier/serviceability/';

    public function check(Request $request): JsonResponse
    {
        $request->validate(['pincode' => 'required|digits:6']);

        $pincode = $request->input('pincode');

        /* Serviceability per pincode barely changes intraday — cache the live
           result so repeat checks skip the (up to 8s) Shiprocket round-trip.
           Fallback responses are never cached. */
        $cacheKey = 'uf_serviceability_'.$pincode;

        if ($cached = Cache::get($cacheKey)) {
            return response()->json($cached);
        }

        $token = $this->getToken();

        if (! $token) {
            return $this->fallback();
        }

        try {
            $response = Http::withToken($token)
                ->timeout(8)
                ->get(self::SERVICEABILITY_URL, [
                    'pickup_postcode'   => config('shiprocket.pickup_pincode', '328001'),
                    'delivery_postcode' => $pincode,
                    'weight'            => 0.5,
                    'cod'               => 1,
                ]);

            if ($response->successful()) {
                $available = $response->json('data.available_courier_companies', []);

                if (empty($available)) {
                    $payload = ['deliverable' => false];

                    Cache::put($cacheKey, $payload, 4 * 3600);

                    return response()->json($payload);
                }

                $best = collect($available)->sortBy('estimated_delivery_days')->first();
                $days = $this->formatEtd($best);
                $cod  = (bool) ($best['cod'] ?? false);

                $payload = [
                    'deliverable' => true,
                    'days'        => $days,
                    'cod'         => $cod,
                    'free'        => true,
                ];

                Cache::put($cacheKey, $payload, 4 * 3600);

                return response()->json($payload);
            }

            if ($response->status() === 401) {
                Cache::forget('shiprocket_token');
            }
        } catch (\Exception $e) {
            Log::warning('Delivery check failed', [
                'pincode' => $pincode,
                'error'   => $e->getMessage(),
            ]);
        }

        return $this->fallback();
    }

    private function formatEtd(array $courier): string
    {
        $etd     = $courier['etd'] ?? null;
        $daysNum = $courier['estimated_delivery_days'] ?? null;

        if ($etd) {
            try {
                return Carbon::parse($etd)->setTimezone('Asia/Kolkata')->format('D, d M');
            } catch (\Exception $e) {
                // fall through
            }
        }

        return $daysNum ? $daysNum . ' days' : '5–7 days';
    }

    private function fallback(): JsonResponse
    {
        return response()->json([
            'deliverable' => true,
            'days'        => '5–7 days',
            'cod'         => true,
            'free'        => true,
        ]);
    }

    private function getToken(): ?string
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

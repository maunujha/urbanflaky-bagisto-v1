<?php

namespace App\Services\Shiprocket;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Can we deliver to a pincode, and can the courier collect cash there?
 *
 * Results are cached per pincode and weight band. `check()` returns null
 * when Shiprocket can't be reached: callers fail open (keep selling) rather
 * than block checkout on a courier API outage.
 */
class ServiceabilityService
{
    public function __construct(
        protected ShiprocketClient $client,
        protected ShiprocketSettings $settings,
    ) {}

    /**
     * @return array{serviceable: bool, cod: bool, etd: ?string, days: ?int, couriers: array}|null
     */
    public function check(string $pincode, float $weightKg = 0.5): ?array
    {
        if (! preg_match('/^\d{6}$/', $pincode) || ! $this->client->isConfigured()) {
            return null;
        }

        /* Half-kilo bands: apparel orders rarely leave the first one. */
        $band = max(0.5, ceil($weightKg * 2) / 2);

        $key = "shiprocket:serviceability:{$this->settings->pickupPincode()}:{$pincode}:{$band}";

        if (($cached = Cache::get($key)) !== null) {
            return $cached;
        }

        try {
            $response = $this->client->serviceability([
                'pickup_postcode'   => $this->settings->pickupPincode(),
                'delivery_postcode' => $pincode,
                'weight'            => $band,
                'cod'               => 0,
            ]);
        } catch (ShiprocketException $e) {
            /* "No courier service available" arrives as a 404-ish failure on some accounts. */
            if ($e->getCode() === 404 || $e->getCode() === 422) {
                return $this->remember($key, $this->summarise([]));
            }

            Log::warning('Shiprocket serviceability unavailable', ['pincode' => $pincode, 'error' => $e->getMessage()]);

            return null;
        }

        return $this->remember($key, $this->summarise($response['data']['available_courier_companies'] ?? []));
    }

    /**
     * Couriers for an order already in Shiprocket, quoted with its real
     * weight and payment mode. Used by the admin courier picker.
     */
    public function forShiprocketOrder(string $shiprocketOrderId): array
    {
        $response = $this->client->serviceability(['order_id' => $shiprocketOrderId]);

        $recommended = (string) ($response['data']['recommended_courier_company_id'] ?? '');

        return collect($response['data']['available_courier_companies'] ?? [])
            ->map(fn ($c) => $this->courier($c) + ['recommended' => (string) ($c['courier_company_id'] ?? '') === $recommended])
            ->sortBy('rate')
            ->values()
            ->all();
    }

    protected function summarise(array $couriers): array
    {
        $couriers = collect($couriers)->map(fn ($c) => $this->courier($c));

        $fastest = $couriers->filter(fn ($c) => $c['days'] !== null)->sortBy('days')->first()
            ?? $couriers->first();

        return [
            'serviceable' => $couriers->isNotEmpty(),
            'cod'         => $couriers->contains(fn ($c) => $c['cod']),
            'etd'         => $fastest['etd'] ?? null,
            'days'        => $fastest['days'] ?? null,
            'couriers'    => $couriers->values()->all(),
        ];
    }

    protected function courier(array $c): array
    {
        $etd = null;

        if (! empty($c['etd'])) {
            try {
                $etd = Carbon::parse($c['etd'], 'Asia/Kolkata')->toDateString();
            } catch (\Throwable) {
                $etd = null;
            }
        }

        return [
            'id'     => (string) ($c['courier_company_id'] ?? ''),
            'name'   => (string) ($c['courier_name'] ?? ''),
            'rate'   => round((float) ($c['rate'] ?? 0), 2),
            'cod'    => (bool) ($c['cod'] ?? false),
            'etd'    => $etd,
            'days'   => isset($c['estimated_delivery_days']) && is_numeric($c['estimated_delivery_days']) ? (int) $c['estimated_delivery_days'] : null,
            'rating' => isset($c['rating']) ? round((float) $c['rating'], 1) : null,
        ];
    }

    protected function remember(string $key, array $result): array
    {
        Cache::put($key, $result, (int) config('shiprocket.serviceability_cache_ttl'));

        return $result;
    }
}

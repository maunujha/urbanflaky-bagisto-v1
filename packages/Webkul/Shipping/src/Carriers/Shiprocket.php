<?php

namespace Webkul\Shipping\Carriers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\CartShippingRate;

class Shiprocket extends AbstractShipping
{
    protected $code = 'shiprocket';

    protected $method = 'shiprocket_default';

    const AUTH_URL            = 'https://apiv2.shiprocket.in/v1/external/auth/login';
    const SERVICEABILITY_URL  = 'https://apiv2.shiprocket.in/v1/external/courier/serviceability/';

    const MAX_COURIERS = 5;

    /**
     * Check if Shiprocket is configured and active.
     */
    public function isAvailable(): bool
    {
        return ! empty(config('shiprocket.email')) && ! empty(config('shiprocket.password'));
    }

    /**
     * Fetch real-time rates from Shiprocket and return one CartShippingRate per courier.
     */
    public function calculate(): array|false
    {
        if (! $this->isAvailable()) {
            return false;
        }

        $cart = Cart::getCart();

        $shippingAddress = $cart?->shipping_address;

        if (! $shippingAddress?->postcode) {
            return false;
        }

        $token = $this->getToken();

        if (! $token) {
            return false;
        }

        $weight   = $this->getCartWeight($cart);
        $couriers = $this->fetchRates($token, $shippingAddress->postcode, $weight);

        if (empty($couriers)) {
            return false;
        }

        /* Sort by price ascending, take top N */
        usort($couriers, fn ($a, $b) => $a['rate'] <=> $b['rate']);

        $couriers = array_slice($couriers, 0, self::MAX_COURIERS);

        $rates = [];

        foreach ($couriers as $courier) {
            $rate = new CartShippingRate;

            $rate->carrier             = $this->code;
            $rate->carrier_title       = 'Shiprocket';
            $rate->method              = 'shiprocket_' . $courier['courier_company_id'];
            $rate->method_title        = $courier['courier_name'];
            $rate->method_description  = $courier['etd'] ?? ($courier['estimated_delivery_days'] ?? '');
            $rate->price               = (float) $courier['rate'];
            $rate->base_price          = (float) $courier['rate'];

            $rates[] = $rate;
        }

        return $rates ?: false;
    }

    /**
     * Get or refresh the Shiprocket JWT token (cached for 23 hours).
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

                Log::error('Shiprocket auth failed', ['body' => $response->body()]);
            } catch (\Exception $e) {
                Log::error('Shiprocket auth exception', ['message' => $e->getMessage()]);
            }

            return null;
        });
    }

    /**
     * Call Shiprocket serviceability API and return available courier companies.
     */
    protected function fetchRates(string $token, string $deliveryPincode, float $weight): array
    {
        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->get(self::SERVICEABILITY_URL, [
                    'pickup_postcode'   => config('shiprocket.pickup_pincode'),
                    'delivery_postcode' => $deliveryPincode,
                    'weight'            => $weight,
                    'cod'               => 1,
                ]);

            if ($response->successful()) {
                return $response->json('data.available_courier_companies') ?? [];
            }

            /* Token may have expired — clear cache and let next request re-auth */
            if ($response->status() === 401) {
                Cache::forget('shiprocket_token');
            }

            Log::error('Shiprocket serviceability failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('Shiprocket serviceability exception', ['message' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Sum cart item weights (product weight × quantity), minimum 0.5 kg.
     */
    protected function getCartWeight($cart): float
    {
        $weight = 0.0;

        foreach ($cart->items as $item) {
            $weight += ((float) ($item->product->weight ?? 0.5)) * $item->quantity;
        }

        return max(round($weight, 2), 0.5);
    }

    /* Satisfy AbstractShipping interface — not used for Shiprocket */
    public function getCode(): string       { return $this->code; }
    public function getTitle(): string      { return 'Shiprocket'; }
    public function getDescription(): string { return 'Courier delivery via Shiprocket'; }
}

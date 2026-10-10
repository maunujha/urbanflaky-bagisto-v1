<?php

namespace App\Services\Shiprocket;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin HTTP client for the Shiprocket v1 external API.
 *
 * The single owner of authentication: the JWT is cached in Redis and shared
 * by web and queue processes, and a 401 clears it and retries once. Every
 * failure surfaces as a ShiprocketException so callers decide whether to
 * retry, record or fall back.
 */
class ShiprocketClient
{
    public const TOKEN_KEY = 'shiprocket_token';

    public function isConfigured(): bool
    {
        return filled(config('shiprocket.email')) && filled(config('shiprocket.password'));
    }

    /**
     * Couriers serving a pincode pair. `order_id` (Shiprocket's) may replace
     * the postcodes to quote an existing order with its own weight and COD flag.
     */
    public function serviceability(array $query): array
    {
        return $this->get('courier/serviceability/', $query);
    }

    public function createOrder(array $payload): array
    {
        return $this->post('orders/create/adhoc', $payload);
    }

    public function showOrder(string $shiprocketOrderId): array
    {
        return $this->get('orders/show/'.$shiprocketOrderId);
    }

    /**
     * Shiprocket orders whose channel order id matches, newest first.
     */
    public function searchOrders(string $search): array
    {
        return $this->get('orders', ['search' => $search, 'per_page' => 10]);
    }

    /**
     * Without a courier id Shiprocket applies the account's courier priority.
     */
    public function assignAwb(string $shipmentId, ?string $courierId = null): array
    {
        return $this->post('courier/assign/awb', array_filter([
            'shipment_id' => $shipmentId,
            'courier_id'  => $courierId,
        ]));
    }

    /**
     * Releases assigned AWBs so a shipment can be given to another courier.
     */
    public function cancelAwbs(array $awbs): array
    {
        return $this->post('orders/cancel/shipment/awbs', ['awbs' => array_values($awbs)]);
    }

    public function generatePickup(string $shipmentId): array
    {
        return $this->post('courier/generate/pickup', ['shipment_id' => [$shipmentId]]);
    }

    public function generateLabel(string $shipmentId): array
    {
        return $this->post('courier/generate/label', ['shipment_id' => [$shipmentId]]);
    }

    public function generateManifest(string $shipmentId): array
    {
        return $this->post('manifests/generate', ['shipment_id' => [$shipmentId]]);
    }

    public function printManifest(string $shiprocketOrderId): array
    {
        return $this->post('manifests/print', ['order_ids' => [$shiprocketOrderId]]);
    }

    public function printInvoice(string $shiprocketOrderId): array
    {
        return $this->post('orders/print/invoice', ['ids' => [$shiprocketOrderId]]);
    }

    public function cancelOrders(array $shiprocketOrderIds): array
    {
        return $this->post('orders/cancel', ['ids' => array_values($shiprocketOrderIds)]);
    }

    public function trackAwb(string $awb): array
    {
        return $this->get('courier/track/awb/'.rawurlencode($awb));
    }

    public function get(string $path, array $query = []): array
    {
        return $this->send('get', $path, $query);
    }

    public function post(string $path, array $data = []): array
    {
        return $this->send('post', $path, $data);
    }

    protected function send(string $method, string $path, array $data, bool $isRetry = false): array
    {
        $token = $this->token();

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout((int) config('shiprocket.timeout', 15))
                ->{$method}(config('shiprocket.base_url').$path, $data);
        } catch (ConnectionException $e) {
            throw new ShiprocketException("Shiprocket {$path} unreachable: {$e->getMessage()}", 0, $e);
        }

        if ($response->status() === 401 && ! $isRetry) {
            Cache::forget(self::TOKEN_KEY);

            return $this->send($method, $path, $data, true);
        }

        if ($response->failed()) {
            throw ShiprocketException::fromResponse($response, $path);
        }

        return $response->json() ?? [];
    }

    protected function token(): string
    {
        if ($token = Cache::get(self::TOKEN_KEY)) {
            return $token;
        }

        if (! $this->isConfigured()) {
            throw new ShiprocketException('Shiprocket credentials are not configured.');
        }

        try {
            $response = Http::acceptJson()->timeout(10)->post(config('shiprocket.base_url').'auth/login', [
                'email'    => config('shiprocket.email'),
                'password' => config('shiprocket.password'),
            ]);
        } catch (ConnectionException $e) {
            throw new ShiprocketException("Shiprocket auth unreachable: {$e->getMessage()}", 0, $e);
        }

        if (! $response->successful() || ! ($token = $response->json('token'))) {
            Log::error('Shiprocket auth failed', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 300)]);

            throw ShiprocketException::fromResponse($response, 'auth/login');
        }

        Cache::put(self::TOKEN_KEY, $token, (int) config('shiprocket.token_ttl'));

        return $token;
    }
}

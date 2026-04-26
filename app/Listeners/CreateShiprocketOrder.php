<?php

namespace App\Listeners;

use App\Models\ShiprocketOrder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Models\CountryState;

class CreateShiprocketOrder implements ShouldQueue
{
    /**
     * Retry once on failure, with 10s delay.
     */
    public int $tries = 2;

    public int $backoff = 10;
    const AUTH_URL   = 'https://apiv2.shiprocket.in/v1/external/auth/login';
    const ORDER_URL  = 'https://apiv2.shiprocket.in/v1/external/orders/create/adhoc';

    /**
     * Fire after every Bagisto order is created.
     */
    public function handle($order): void
    {
        /* Only push stockable orders that need physical shipping */
        if (! $order->haveStockableItems()) {
            return;
        }

        $token = $this->getToken();

        if (! $token) {
            Log::error('Shiprocket: could not get auth token for order ' . $order->increment_id);
            return;
        }

        $payload = $this->buildPayload($order);

        try {
            $response = Http::withToken($token)->timeout(15)->post(self::ORDER_URL, $payload);

            if ($response->successful()) {
                $awb         = $response->json('awb_code') ?? '';
                $shipmentId  = (string) ($response->json('shipment_id') ?? '');
                $srOrderId   = (string) ($response->json('order_id') ?? '');
                $courierName = $response->json('courier_name') ?? '';

                ShiprocketOrder::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'shiprocket_order_id' => $srOrderId,
                        'shipment_id'         => $shipmentId,
                        'awb_code'            => $awb,
                        'courier_name'        => $courierName,
                        'status'              => 'created',
                    ]
                );

                Log::info('Shiprocket order created', [
                    'order'            => $order->increment_id,
                    'shiprocket_order' => $srOrderId,
                    'shipment_id'      => $shipmentId,
                    'awb'              => $awb,
                ]);
                return;
            }

            if ($response->status() === 401) {
                Cache::forget('shiprocket_token');
            }

            Log::error('Shiprocket order creation failed', [
                'order'    => $order->increment_id,
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('Shiprocket order exception', [
                'order'   => $order->increment_id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build the Shiprocket order payload from a Bagisto order.
     */
    protected function buildPayload($order): array
    {
        $shipping = $order->shipping_address;
        $billing  = $order->billing_address;

        $paymentMethod = match ($order->payment?->method) {
            'cashondelivery' => 'COD',
            default          => 'Prepaid',
        };

        $items       = [];
        $totalWeight = 0.0;

        foreach ($order->items as $item) {
            $weightGrams  = (float) ($item->product?->weight ?? 500);
            $weightKg     = $weightGrams / 1000;
            $totalWeight += $weightKg * $item->qty_ordered;

            $items[] = [
                'name'          => $item->name,
                'sku'           => $item->sku,
                'units'         => (int) $item->qty_ordered,
                'selling_price' => round($item->base_price, 2),
                'discount'      => 0,
                'tax'           => round($item->tax_amount ?? 0, 2),
                'hsn'           => 0,
            ];
        }

        $totalWeight = max(round($totalWeight, 2), 0.5);

        $sameAsBilling = $shipping && $billing
            && $shipping->postcode === $billing->postcode
            && $shipping->address  === $billing->address;

        return [
            'order_id'                => (string) $order->increment_id,
            'order_date'              => $order->created_at->format('Y-m-d H:i'),
            'pickup_location'         => config('shiprocket.pickup_location', 'home'),

            /* Billing */
            'billing_customer_name'   => $billing?->first_name ?? $order->customer_first_name,
            'billing_last_name'       => $billing?->last_name  ?? $order->customer_last_name,
            'billing_address'         => $billing?->address     ?? '',
            'billing_address_2'       => '',
            'billing_city'            => $billing?->city        ?? '',
            'billing_pincode'         => $billing?->postcode    ?? '',
            'billing_state'           => $this->getStateName($billing?->state ?? ''),
            'billing_country'         => 'India',
            'billing_email'           => $billing?->email       ?? $order->customer_email ?? '',
            'billing_phone'           => $billing?->phone       ?? '',

            /* Shipping */
            'shipping_is_billing'     => $sameAsBilling,
            'shipping_customer_name'  => $shipping?->first_name ?? $order->customer_first_name,
            'shipping_last_name'      => $shipping?->last_name  ?? $order->customer_last_name,
            'shipping_address'        => $shipping?->address     ?? '',
            'shipping_address_2'      => '',
            'shipping_city'           => $shipping?->city        ?? '',
            'shipping_pincode'        => $shipping?->postcode    ?? '',
            'shipping_state'          => $this->getStateName($shipping?->state ?? ''),
            'shipping_country'        => 'India',
            'shipping_email'          => $shipping?->email       ?? $order->customer_email ?? '',
            'shipping_phone'          => $shipping?->phone       ?? '',

            /* Items */
            'order_items'             => $items,

            /* Totals */
            'payment_method'          => $paymentMethod,
            'shipping_charges'        => round($order->shipping_amount ?? 0, 2),
            'giftwrap_charges'        => 0,
            'transaction_charges'     => 0,
            'total_discount'          => round($order->discount_amount ?? 0, 2),
            'sub_total'               => round($order->base_sub_total, 2),

            /* Package dimensions (defaults — update if product dims added later) */
            'length'                  => 10,
            'breadth'                 => 10,
            'height'                  => 10,
            'weight'                  => $totalWeight,
        ];
    }

    /**
     * Convert Bagisto state code (e.g. "RJ") to full name (e.g. "Rajasthan").
     */
    protected function getStateName(string $code): string
    {
        if (! $code) {
            return '';
        }

        return CountryState::where('country_code', 'IN')
            ->where('code', $code)
            ->value('default_name') ?? $code;
    }

    /**
     * Get cached Shiprocket JWT (shared with the carrier class).
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
                Log::error('Shiprocket auth exception', ['message' => $e->getMessage()]);
            }

            return null;
        });
    }
}

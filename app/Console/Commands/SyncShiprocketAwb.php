<?php

namespace App\Console\Commands;

use App\Models\ShiprocketOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncShiprocketAwb extends Command
{
    protected $signature   = 'shiprocket:sync-awb {--order= : Specific order increment_id to sync}';
    protected $description = 'Fetch AWB codes from Shiprocket for orders missing tracking info';

    const AUTH_URL  = 'https://apiv2.shiprocket.in/v1/external/auth/login';
    const ORDER_URL = 'https://apiv2.shiprocket.in/v1/external/orders/show/';

    public function handle(): void
    {
        $token = $this->getToken();

        if (! $token) {
            $this->error('Could not authenticate with Shiprocket.');
            return;
        }

        $query = ShiprocketOrder::with('order')
            ->where(function ($q) {
                $q->whereNull('awb_code')->orWhere('awb_code', '');
            });

        if ($specificId = $this->option('order')) {
            $query->whereHas('order', fn ($q) => $q->where('increment_id', $specificId));
        }

        $records = $query->get();

        if ($records->isEmpty()) {
            $this->info('No orders need AWB sync.');
            return;
        }

        $this->info("Syncing {$records->count()} order(s)...");

        foreach ($records as $srOrder) {
            $this->syncOrder($token, $srOrder);
        }

        $this->info('Done.');
    }

    protected function syncOrder(string $token, ShiprocketOrder $srOrder): void
    {
        $incrementId = $srOrder->order->increment_id;

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->get(self::ORDER_URL . $srOrder->shiprocket_order_id);

            if (! $response->successful()) {
                $this->warn("Order #{$incrementId}: API error {$response->status()}");
                return;
            }

            $data    = $response->json('data') ?? [];
            $awb     = $data['awb_code']     ?? $data['shipments'][0]['awb'] ?? null;
            $courier = $data['courier_name'] ?? $data['shipments'][0]['courier'] ?? null;
            $status  = $data['status']       ?? null;

            if ($awb) {
                $srOrder->update([
                    'awb_code'     => $awb,
                    'courier_name' => $courier ?? $srOrder->courier_name,
                    'status'       => $status  ?? $srOrder->status,
                ]);
                $this->info("Order #{$incrementId}: AWB = {$awb} ({$courier})");
            } else {
                $this->line("Order #{$incrementId}: AWB not yet assigned.");
            }
        } catch (\Exception $e) {
            $this->error("Order #{$incrementId}: {$e->getMessage()}");
            Log::error('SyncShiprocketAwb error', ['order' => $incrementId, 'error' => $e->getMessage()]);
        }
    }

    protected function getToken(): ?string
    {
        return Cache::remember('shiprocket_token', 23 * 3600, function () {
            $response = Http::timeout(10)->post(self::AUTH_URL, [
                'email'    => config('shiprocket.email'),
                'password' => config('shiprocket.password'),
            ]);
            return $response->successful() ? $response->json('token') : null;
        });
    }
}

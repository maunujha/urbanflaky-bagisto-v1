<?php

namespace App\Providers;

use App\Jobs\Shiprocket\CancelShiprocketOrder;
use App\Jobs\Shiprocket\PushOrderToShiprocket;
use App\Models\ShiprocketOrder;
use App\Services\Shiprocket\ShiprocketClient;
use App\Services\Shiprocket\ShiprocketSettings;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\Sales\Models\Order;

/**
 * Shiprocket fulfilment: order push, courier assignment, tracking and the
 * admin order panel. Settings live in Admin → Configure → Sales → Shiprocket.
 */
class ShiprocketServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(base_path('config/shiprocket-system.php'), 'core');

        $this->mergeConfigFrom(base_path('config/shiprocket-acl.php'), 'acl');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('routes/shiprocket-admin.php'));

        $this->loadViewsFrom(resource_path('views/shiprocket'), 'shiprocket');

        $this->extendCoreConfigSections();

        /* Prepaid orders only exist once Razorpay has captured the payment,
           so "order saved" is the right moment for both COD and prepaid. */
        Event::listen('checkout.order.save.after', function ($order) {
            if (app(ShiprocketClient::class)->isConfigured() && app(ShiprocketSettings::class)->autoPush()) {
                PushOrderToShiprocket::dispatch($order->id)->afterCommit();
            }
        });

        Event::listen('sales.order.cancel.after', function ($order) {
            $shipment = ShiprocketOrder::where('order_id', $order->id)->first();

            if ($order->status === Order::STATUS_CANCELED && $shipment?->isBeforePickup()) {
                CancelShiprocketOrder::dispatch($order->id)->afterCommit();
            }
        });

        Event::listen('bagisto.admin.sales.order.right_component.before', function ($viewRenderEventManager) {
            /* The same hook renders on the create-order page, which has no order. */
            if ($viewRenderEventManager->getParam('order') instanceof Order && bouncer()->hasPermission('sales.orders.shiprocket')) {
                $viewRenderEventManager->addTemplate('shiprocket::admin.order-panel');
            }
        });
    }

    /**
     * Append the free-shipping minimum and COD fields (config/shipping-system.php)
     * to Bagisto's own Shipping and Payment sections, without editing core.
     * Runs at boot, after every package has merged its `core` config, and
     * skips fields already present so a cached config is not doubled.
     */
    protected function extendCoreConfigSections(): void
    {
        $extra = require base_path('config/shipping-system.php');

        config(['core' => array_map(function ($item) use ($extra) {
            foreach ($extra[$item['key'] ?? ''] ?? [] as $field) {
                if (! collect($item['fields'] ?? [])->contains('name', $field['name'])) {
                    $item['fields'][] = $field;
                }
            }

            return $item;
        }, config('core', []))]);
    }
}

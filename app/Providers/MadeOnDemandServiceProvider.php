<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the "Made on Demand" fulfillment feature into the admin panel.
 *
 * The storefront surfaces (product card badge, PDP notice, cart box, checkout
 * notice) and the data layer (migrations, models, resources) are handled in
 * their respective packages; this provider only injects the admin toggle so the
 * feature stays modular and free of core edits.
 */
class MadeOnDemandServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(resource_path('views/made-on-demand'), 'made-on-demand');

        /**
         * Append the "Fulfillment" section to the product edit form. The listener
         * receives the ViewRenderEventManager whose params already carry the
         * `product` model passed by the render event.
         */
        Event::listen('bagisto.admin.catalog.product.edit.form.after', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('made-on-demand::admin.fulfillment');
        });
    }
}

<?php

namespace Webkul\Shop\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventCheckoutInCatalogMode
{
    /**
     * Block checkout, payment, and order-placement routes while catalog mode is enabled.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! core()->getConfigData('general.catalog_mode.settings.enabled')) {
            return $next($request);
        }

        $message = trans('shop::app.checkout.catalog-mode.unavailable');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], 403);
        }

        session()->flash('warning', $message);

        return redirect()->route('shop.checkout.cart.index');
    }
}

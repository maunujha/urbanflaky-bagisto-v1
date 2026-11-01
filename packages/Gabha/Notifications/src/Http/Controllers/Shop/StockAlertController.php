<?php

namespace Gabha\Notifications\Http\Controllers\Shop;

use App\Services\Security\RecaptchaVerifier;
use Gabha\Notifications\Models\StockAlert;
use Gabha\Notifications\Services\AutomationSettings;
use Gabha\Notifications\Services\EmailDispatcher;
use Gabha\Notifications\Support\StockStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Product\Models\Product;

/**
 * "Email me when it's back". Nothing is emailed at sign-up, so the form can
 * never be used to send mail to someone else's inbox; the only email is the
 * restock notice itself.
 */
class StockAlertController extends Controller
{
    public function store(Request $request, RecaptchaVerifier $recaptcha, AutomationSettings $settings): JsonResponse
    {
        if (! $settings->enabled('lifecycle.back_in_stock')) {
            return response()->json(['message' => trans('notifications::app.stock.unavailable')], 404);
        }

        /* Honeypot: bots fill every field; pretend it worked. */
        if (filled($request->input('website'))) {
            return response()->json(['message' => trans('notifications::app.stock.saved')]);
        }

        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'email'      => ['required', 'string', 'email:rfc,strict', 'max:160'],
        ]);

        if (! $recaptcha->passes($request->input('recaptcha_token'), 'stock_alert')) {
            return response()->json(['message' => trans('notifications::app.stock.verify-failed')], 422);
        }

        $email = mb_strtolower(trim($data['email']));

        if (! EmailDispatcher::deliverable($email)) {
            return response()->json(['message' => trans('notifications::app.stock.invalid-email')], 422);
        }

        $product = Product::find($data['product_id']);

        if (! $product || ! StockStatus::canWaitFor($product)) {
            return response()->json(['message' => trans('notifications::app.stock.in-stock')], 422);
        }

        StockAlert::firstOrCreate(
            ['product_id' => $product->id, 'email' => $email, 'status' => StockAlert::PENDING],
            ['customer_id' => auth()->guard('customer')->id()],
        );

        return response()->json(['message' => trans('notifications::app.stock.saved')]);
    }
}

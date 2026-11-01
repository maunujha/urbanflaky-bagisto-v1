<?php

namespace Gabha\Notifications\Http\Controllers\Admin;

use Gabha\Notifications\Mail\AbandonedCart;
use Gabha\Notifications\Mail\OrderConfirmation;
use Gabha\Notifications\Mail\ReviewRequest;
use Gabha\Notifications\Mail\ShipmentUpdate;
use Gabha\Notifications\Mail\WinBack;
use Gabha\Notifications\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Mail\Mailable;
use Illuminate\Routing\Controller;
use Webkul\Checkout\Models\Cart;
use Webkul\Customer\Models\Customer;
use Webkul\Sales\Models\Order;

class EmailLogController extends Controller
{
    /** Preview key => label. */
    public const PREVIEWS = [
        'order_confirmation'        => 'Order confirmation',
        'shipment.processing'       => 'Being packed',
        'shipment.out_for_delivery' => 'Out for delivery',
        'shipment.delivered'        => 'Delivered',
        'shipment.delivery_failed'  => 'Delivery attempt failed',
        'shipment.rto'              => 'Returning to us',
        'review_request'            => 'Review request',
        'abandoned_cart'            => 'Abandoned bag',
        'win_back'                  => 'Win-back',
    ];

    public function index(Request $request)
    {
        $logs = EmailLog::query()
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('type'), fn ($query, $type) => $query->where('type', $type))
            ->when($request->query('q'), fn ($query, $q) => $query->where('recipient', 'like', '%'.mb_strtolower($q).'%'))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('notifications::admin.logs.index', [
            'logs'     => $logs,
            'types'    => EmailLog::query()->distinct()->orderBy('type')->pluck('type'),
            'previews' => self::PREVIEWS,
            'stats'    => [
                'sent-today'      => EmailLog::where('status', EmailLog::STATUS_SENT)->where('sent_at', '>=', now()->startOfDay())->count(),
                'failed-week'     => EmailLog::where('status', EmailLog::STATUS_FAILED)->where('created_at', '>=', now()->subDays(7))->count(),
                'marketing-today' => EmailLog::where('category', EmailLog::CATEGORY_MARKETING)->where('created_at', '>=', now()->startOfDay())->count()
                    .' / '.config('notifications.limits.marketing_daily_cap'),
            ],
        ]);
    }

    /**
     * Renders an email with the latest real data. Never sends anything.
     */
    public function preview(string $type): Response
    {
        abort_unless(isset(self::PREVIEWS[$type]), 404);

        $mailable = $this->sample($type);

        if (! $mailable) {
            return response(trans('notifications::app.admin.no-preview'));
        }

        return response($mailable->render());
    }

    protected function sample(string $type): ?Mailable
    {
        $order = Order::latest('id')->first();

        if (str_starts_with($type, 'shipment.')) {
            return $order ? new ShipmentUpdate($order, substr($type, 9)) : null;
        }

        return match ($type) {
            'order_confirmation' => $order ? new OrderConfirmation($order) : null,
            'review_request'     => $order ? new ReviewRequest($order, '#') : null,
            'abandoned_cart'     => ($cart = Cart::where('items_count', '>', 0)->latest('id')->first())
                ? new AbandonedCart($cart, 1, route('shop.checkout.cart.index'), '#')
                : null,
            'win_back'           => ($customer = Customer::latest('id')->first()) ? new WinBack($customer, '#') : null,
        };
    }
}

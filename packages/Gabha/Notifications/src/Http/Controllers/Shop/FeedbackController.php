<?php

namespace Gabha\Notifications\Http\Controllers\Shop;

use Gabha\Notifications\Models\OrderFeedback;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Sales\Models\Order;

/**
 * The page behind the 1–5 links in the feedback email. Opening it saves
 * nothing (mail scanners open every link); pressing Send does.
 */
class FeedbackController extends Controller
{
    public function show(int $orderId, int $rating)
    {
        $order = Order::findOrFail($orderId);

        return view('notifications::shop.feedback', [
            'order'    => $order,
            'rating'   => max(1, min(5, $rating)),
            'feedback' => OrderFeedback::where('order_id', $order->id)->first(),
            'done'     => false,
        ]);
    }

    public function store(Request $request, int $orderId)
    {
        $order = Order::findOrFail($orderId);

        $data = $request->validate([
            'rating'  => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $feedback = OrderFeedback::updateOrCreate(
            ['order_id' => $order->id],
            ['email' => $order->customer_email, 'rating' => $data['rating'], 'comment' => $data['comment'] ?? null],
        );

        return view('notifications::shop.feedback', [
            'order'    => $order,
            'rating'   => $feedback->rating,
            'feedback' => $feedback,
            'done'     => true,
        ]);
    }
}

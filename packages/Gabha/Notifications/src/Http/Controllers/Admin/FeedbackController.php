<?php

namespace Gabha\Notifications\Http\Controllers\Admin;

use Gabha\Notifications\Models\OrderFeedback;
use Illuminate\Routing\Controller;

/**
 * Ratings customers left from the feedback email.
 */
class FeedbackController extends Controller
{
    public function index()
    {
        return view('notifications::admin.feedback.index', [
            'feedback' => OrderFeedback::with('order')->latest('id')->paginate(30),
            'average'  => round((float) OrderFeedback::avg('rating'), 1),
            'count'    => OrderFeedback::count(),
        ]);
    }
}

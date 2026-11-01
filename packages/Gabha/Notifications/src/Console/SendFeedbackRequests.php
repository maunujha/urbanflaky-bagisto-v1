<?php

namespace Gabha\Notifications\Console;

use Gabha\Notifications\Mail\FeedbackRequest;
use Gabha\Notifications\Models\OrderFeedback;
use Illuminate\Mail\Mailable;
use Webkul\Sales\Models\Order;

/**
 * Optional (admin switch, off by default): a week after delivery, a one-tap
 * 1–5 rating of the whole experience.
 */
class SendFeedbackRequests extends DeliveredOrderFollowUp
{
    protected $signature = 'notifications:feedback-requests {--dry-run : List who would be emailed, send nothing}';

    protected $description = 'Email experience-rating requests for delivered orders';

    protected function switch(): string
    {
        return 'feedback_request';
    }

    protected function delaySetting(): string
    {
        return 'feedback_delay_days';
    }

    protected function type(): string
    {
        return 'lifecycle.feedback_request';
    }

    /** Already rated (from an earlier email or the site): never ask again. */
    protected function skip(Order $order): bool
    {
        return OrderFeedback::where('order_id', $order->id)->exists();
    }

    protected function mailable(Order $order, string $optOutUrl): Mailable
    {
        return new FeedbackRequest($order, $optOutUrl);
    }
}

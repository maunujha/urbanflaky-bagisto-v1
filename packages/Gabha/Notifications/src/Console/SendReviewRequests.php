<?php

namespace Gabha\Notifications\Console;

use Gabha\Notifications\Mail\ReviewRequest;
use Illuminate\Mail\Mailable;
use Webkul\Sales\Models\Order;

/**
 * Asks for a product review a few days (admin: default 3) after delivery.
 */
class SendReviewRequests extends DeliveredOrderFollowUp
{
    protected $signature = 'notifications:review-requests {--dry-run : List who would be emailed, send nothing}';

    protected $description = 'Email review requests for recently delivered orders';

    protected function switch(): string
    {
        return 'review_request';
    }

    protected function delaySetting(): string
    {
        return 'review_delay_days';
    }

    protected function type(): string
    {
        return 'lifecycle.review_request';
    }

    protected function mailable(Order $order, string $optOutUrl): Mailable
    {
        return new ReviewRequest($order, $optOutUrl);
    }
}

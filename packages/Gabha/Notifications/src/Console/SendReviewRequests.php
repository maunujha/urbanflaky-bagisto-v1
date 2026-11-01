<?php

namespace Gabha\Notifications\Console;

use App\Models\ShiprocketOrder;
use App\Services\Shiprocket\ShipmentStage;
use Gabha\Notifications\Mail\ReviewRequest;
use Gabha\Notifications\Models\EmailLog;
use Gabha\Notifications\Models\EmailSuppression;
use Gabha\Notifications\Services\AutomationSettings;
use Gabha\Notifications\Services\EmailDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;
use Webkul\Sales\Models\Order;

/**
 * Asks for a review a few days after Shiprocket confirms delivery. A service
 * email about the customer's own order, so it needs no newsletter opt-in,
 * but carries its own one-click opt-out.
 */
class SendReviewRequests extends Command
{
    protected $signature = 'notifications:review-requests {--dry-run : List who would be emailed, send nothing}';

    protected $description = 'Email review requests for recently delivered orders';

    public function handle(EmailDispatcher $dispatcher, AutomationSettings $settings): int
    {
        if (! $settings->enabled('lifecycle.review_request')) {
            $this->info('Review requests are switched off.');

            return self::SUCCESS;
        }

        $delay = (int) config('notifications.review_request.delay_days', 5);
        $window = (int) config('notifications.review_request.window_days', 3);
        $dryRun = (bool) $this->option('dry-run');

        $budget = (int) config('notifications.limits.review_daily_cap', 100) - EmailLog::where('type', 'lifecycle.review_request')
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        $orderIds = ShiprocketOrder::where('status', ShipmentStage::DELIVERED)
            ->whereBetween('delivered_at', [now()->subDays($delay + $window), now()->subDays($delay)])
            ->pluck('order_id');

        $queued = 0;

        foreach (Order::with('items.product')->whereIn('id', $orderIds)->whereNotIn('status', [Order::STATUS_CANCELED, Order::STATUS_CLOSED])->get() as $order) {
            if ($budget <= 0) {
                break;
            }

            $key = 'review:'.$order->id;

            if (! $order->customer_email
                || $dispatcher->alreadySent($key)
                || EmailSuppression::isSuppressed($order->customer_email, EmailSuppression::SCOPE_REVIEWS)) {
                continue;
            }

            if ($dryRun) {
                $this->line("would ask {$order->customer_email} to review order #{$order->increment_id}");
                $budget--;
                $queued++;

                continue;
            }

            $optOut = URL::signedRoute('shop.notifications.unsubscribe', [
                'email' => mb_strtolower($order->customer_email),
                'scope' => EmailSuppression::SCOPE_REVIEWS,
            ]);

            if ($dispatcher->send(new ReviewRequest($order, $optOut), 'lifecycle.review_request', $key, $order, EmailLog::CATEGORY_LIFECYCLE)) {
                $budget--;
                $queued++;
            }
        }

        $this->info(($dryRun ? "Dry run, nothing sent. Would queue" : "Queued")." {$queued} review request(s).");

        return self::SUCCESS;
    }
}

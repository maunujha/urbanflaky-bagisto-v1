<?php

namespace Gabha\Notifications\Console;

use App\Models\ShiprocketOrder;
use App\Services\Shiprocket\ShipmentStage;
use Gabha\Notifications\Models\EmailLog;
use Gabha\Notifications\Models\EmailSuppression;
use Gabha\Notifications\Services\AutomationSettings;
use Gabha\Notifications\Services\EmailDispatcher;
use Illuminate\Console\Command;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\URL;
use Webkul\Sales\Models\Order;

/**
 * A follow-up a set number of days after Shiprocket confirmed delivery. Only
 * orders with a real delivery date qualify: no courier event, no email.
 * Service emails about the customer's own order, so no newsletter opt-in is
 * needed, but each carries a one-click opt-out and they share a daily cap.
 */
abstract class DeliveredOrderFollowUp extends Command
{
    /** Admin switch under emails.automation.lifecycle. */
    abstract protected function switch(): string;

    /** Delay setting under emails.automation.timing. */
    abstract protected function delaySetting(): string;

    /** Email type for the log, e.g. "lifecycle.review_request". */
    abstract protected function type(): string;

    abstract protected function mailable(Order $order, string $optOutUrl): Mailable;

    protected function skip(Order $order): bool
    {
        return false;
    }

    public function handle(EmailDispatcher $dispatcher, AutomationSettings $settings): int
    {
        if (! $settings->enabled('lifecycle.'.$this->switch())) {
            $this->info('Switched off in admin.');

            return self::SUCCESS;
        }

        $delay = $settings->number('timing.'.$this->delaySetting());
        $window = (int) config('notifications.review_request.window_days', 3);
        $dryRun = (bool) $this->option('dry-run');

        $budget = (int) config('notifications.limits.review_daily_cap', 100) - EmailLog::where('category', EmailLog::CATEGORY_LIFECYCLE)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        $orderIds = ShiprocketOrder::where('status', ShipmentStage::DELIVERED)
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [now()->subDays($delay + $window), now()->subDays($delay)])
            ->pluck('order_id');

        $queued = 0;

        $orders = Order::with('items.product')
            ->whereIn('id', $orderIds)
            ->whereNotIn('status', [Order::STATUS_CANCELED, Order::STATUS_CLOSED])
            ->get();

        foreach ($orders as $order) {
            if ($budget <= 0) {
                break;
            }

            $key = $this->type().':'.$order->id;

            if (! EmailDispatcher::deliverable($order->customer_email)
                || $this->skip($order)
                || $dispatcher->alreadySent($key)
                || EmailSuppression::isSuppressed($order->customer_email, EmailSuppression::SCOPE_REVIEWS)) {
                continue;
            }

            $budget--;
            $queued++;

            if ($dryRun) {
                $this->line("would email {$order->customer_email} about order #{$order->increment_id}");

                continue;
            }

            $optOut = URL::signedRoute('shop.notifications.unsubscribe', [
                'email' => mb_strtolower($order->customer_email),
                'scope' => EmailSuppression::SCOPE_REVIEWS,
            ]);

            if (! $dispatcher->send($this->mailable($order, $optOut), $this->type(), $key, $order, EmailLog::CATEGORY_LIFECYCLE)) {
                $budget++;
                $queued--;
            }
        }

        $this->info(($dryRun ? 'Dry run, nothing sent. Would queue' : 'Queued')." {$queued} email(s).");

        return self::SUCCESS;
    }
}

<?php

namespace Gabha\Notifications\Console;

use Gabha\Notifications\Mail\WinBack;
use Gabha\Notifications\Models\EmailLog;
use Gabha\Notifications\Models\EmailSuppression;
use Gabha\Notifications\Services\AutomationSettings;
use Gabha\Notifications\Services\EmailDispatcher;
use Gabha\Notifications\Services\MarketingGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;
use Webkul\Customer\Models\Customer;
use Webkul\Sales\Models\Order;

/**
 * One email to a subscribed customer whose last order has gone quiet; never
 * again until they order once more.
 */
class SendWinBackEmails extends Command
{
    protected $signature = 'notifications:win-back {--dry-run : List who would be emailed, send nothing}';

    protected $description = 'Email subscribed customers whose last order was months ago';

    public function handle(EmailDispatcher $dispatcher, MarketingGuard $guard, AutomationSettings $settings): int
    {
        if (! $settings->enabled('lifecycle.win_back')) {
            $this->info('Win-back emails are switched off.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $budget = $guard->remainingToday();
        $queued = 0;

        $lapsed = Order::query()
            ->selectRaw('customer_id, MAX(id) as last_order_id, MAX(created_at) as last_ordered_at')
            ->whereNotNull('customer_id')
            ->whereNotIn('status', [Order::STATUS_CANCELED])
            ->groupBy('customer_id')
            ->havingRaw('MAX(created_at) BETWEEN ? AND ?', [
                now()->subDays((int) config('notifications.win_back.max_days', 365)),
                now()->subDays((int) config('notifications.win_back.min_days', 90)),
            ])
            ->get();

        foreach ($lapsed as $row) {
            if ($budget <= 0) {
                break;
            }

            $customer = Customer::find($row->customer_id);
            $key = 'winback:'.$row->customer_id.':'.$row->last_order_id;

            if (! $customer?->email || $dispatcher->alreadySent($key)) {
                continue;
            }

            if ($reason = $guard->blockedReason($customer->email)) {
                $this->line("skip customer {$customer->id}: {$reason}");

                continue;
            }

            if ($dryRun) {
                $this->line("would send win-back to {$customer->email}");
                $budget--;
                $queued++;

                continue;
            }

            $unsubscribe = URL::signedRoute('shop.notifications.unsubscribe', [
                'email' => mb_strtolower($customer->email),
                'scope' => EmailSuppression::SCOPE_MARKETING,
            ]);

            if ($dispatcher->send(new WinBack($customer, $unsubscribe), 'marketing.win_back', $key, $customer, EmailLog::CATEGORY_MARKETING)) {
                $budget--;
                $queued++;
            }
        }

        $this->info(($dryRun ? "Dry run, nothing sent. Would queue" : "Queued")." {$queued} win-back email(s).");

        return self::SUCCESS;
    }
}

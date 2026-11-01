<?php

namespace Gabha\Notifications\Console;

use Gabha\Notifications\Mail\BackInStock;
use Gabha\Notifications\Models\StockAlert;
use Gabha\Notifications\Services\AutomationSettings;
use Gabha\Notifications\Services\EmailDispatcher;
use Gabha\Notifications\Support\ProductPresenter;
use Gabha\Notifications\Support\StockStatus;
use Illuminate\Console\Command;

/**
 * Checks waiting "email me when it's back" requests against live stock, and
 * emails each one once its product can really be bought again. Stock is read
 * at send time, so it works however the stock came back (admin edit, purchase
 * entry, import, cancelled order).
 */
class SendBackInStockAlerts extends Command
{
    protected $signature = 'notifications:back-in-stock {--dry-run : List who would be emailed, send nothing}';

    protected $description = 'Email customers waiting for a product that is back in stock';

    public function handle(EmailDispatcher $dispatcher, AutomationSettings $settings): int
    {
        if (! $settings->enabled('lifecycle.back_in_stock')) {
            $this->info('Back-in-stock emails are switched off.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun) {
            StockAlert::where('status', StockAlert::PENDING)
                ->where('created_at', '<', now()->subDays((int) config('notifications.back_in_stock.expire_days', 90)))
                ->update(['status' => StockAlert::EXPIRED]);
        }

        $budget = (int) config('notifications.back_in_stock.per_run', 50);
        $queued = 0;
        $inStock = [];

        $alerts = StockAlert::with('product.parent')
            ->where('status', StockAlert::PENDING)
            ->orderBy('id')
            ->get();

        foreach ($alerts as $alert) {
            if ($queued >= $budget) {
                break;
            }

            $product = $alert->product;

            if (! $product || ! ($inStock[$product->id] ??= StockStatus::inStock($product)) || ! ProductPresenter::url($product)) {
                continue;
            }

            if ($dryRun) {
                $this->line("would tell {$alert->email} that product {$product->id} is back");
                $queued++;

                continue;
            }

            /* Stays pending if it could not be queued, so the next run retries. */
            $sent = $dispatcher->send(new BackInStock($alert), 'lifecycle.back_in_stock', 'stock:'.$alert->id, $alert);

            if ($sent || $dispatcher->alreadySent('stock:'.$alert->id)) {
                $alert->update(['status' => StockAlert::NOTIFIED, 'notified_at' => now()]);
                $queued += (int) $sent;
            }
        }

        $this->info(($dryRun ? 'Dry run, nothing sent. Would queue' : 'Queued')." {$queued} back-in-stock email(s).");

        return self::SUCCESS;
    }
}

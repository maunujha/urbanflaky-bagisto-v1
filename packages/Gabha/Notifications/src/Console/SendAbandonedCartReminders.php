<?php

namespace Gabha\Notifications\Console;

use Gabha\Notifications\Mail\AbandonedCart;
use Gabha\Notifications\Models\EmailLog;
use Gabha\Notifications\Models\EmailSuppression;
use Gabha\Notifications\Services\AutomationSettings;
use Gabha\Notifications\Services\EmailDispatcher;
use Gabha\Notifications\Services\MarketingGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;
use Webkul\Checkout\Models\Cart;
use Webkul\Sales\Models\Order;

/**
 * Reminds newsletter subscribers about a checkout they left unfinished,
 * including a Razorpay payment that never completed (no order exists then).
 */
class SendAbandonedCartReminders extends Command
{
    protected $signature = 'notifications:abandoned-carts {--dry-run : List who would be emailed, send nothing}';

    protected $description = 'Email abandoned-bag and failed-payment reminders to subscribed customers';

    public function handle(EmailDispatcher $dispatcher, MarketingGuard $guard, AutomationSettings $settings): int
    {
        if (! $settings->enabled('lifecycle.abandoned_cart')) {
            $this->info('Abandoned bag reminders are switched off.');

            return self::SUCCESS;
        }

        $steps = config('notifications.abandoned_cart.steps', [1 => 1]);
        $dryRun = (bool) $this->option('dry-run');
        $budget = $guard->remainingToday();
        $queued = 0;

        Cart::query()
            ->where('is_active', 1)
            ->where('items_count', '>', 0)
            ->whereNotNull('customer_email')
            ->where('updated_at', '>=', now()->subHours((int) config('notifications.abandoned_cart.max_age_hours', 96)))
            ->where('updated_at', '<=', now()->subHours(min($steps)))
            ->orderBy('updated_at')
            ->each(function (Cart $cart) use ($dispatcher, $guard, $steps, $dryRun, &$budget, &$queued) {
                if ($budget <= 0) {
                    return false;
                }

                $step = $this->dueStep($cart, $steps, $dispatcher);

                if (! $step || $this->hasOrdered($cart)) {
                    return;
                }

                if ($reason = $guard->blockedReason($cart->customer_email)) {
                    $this->line("skip cart {$cart->id}: {$reason}");

                    return;
                }

                if ($dryRun) {
                    $this->line("would send step {$step} to {$cart->customer_email} (cart {$cart->id})");
                    $budget--;
                $queued++;

                    return;
                }

                $mail = new AbandonedCart(
                    $cart,
                    $step,
                    URL::temporarySignedRoute(
                        'shop.notifications.cart.recover',
                        now()->addDays((int) config('notifications.abandoned_cart.link_days', 7)),
                        ['cart' => $cart->id],
                    ),
                    URL::signedRoute('shop.notifications.unsubscribe', [
                        'email' => mb_strtolower($cart->customer_email),
                        'scope' => EmailSuppression::SCOPE_MARKETING,
                    ]),
                );

                if ($dispatcher->send($mail, 'marketing.abandoned_cart', $this->key($cart, $step), $cart, EmailLog::CATEGORY_MARKETING)) {
                    $budget--;
                    $queued++;
                }
            });

        $this->info(($dryRun ? "Dry run, nothing sent. Would queue" : "Queued")." {$queued} reminder(s).");

        return self::SUCCESS;
    }

    /**
     * The first step whose idle time has passed and which this cart has not
     * had yet. Steps are per cart, so editing the bag never restarts them.
     */
    protected function dueStep(Cart $cart, array $steps, EmailDispatcher $dispatcher): ?int
    {
        $idleHours = $cart->updated_at->diffInHours(now());

        foreach ($steps as $step => $hours) {
            if ($idleHours >= $hours && ! $dispatcher->alreadySent($this->key($cart, $step))) {
                return (int) $step;
            }
        }

        return null;
    }

    protected function hasOrdered(Cart $cart): bool
    {
        return Order::where('cart_id', $cart->id)->exists()
            || Order::where('customer_email', $cart->customer_email)->where('created_at', '>=', $cart->created_at)->exists();
    }

    protected function key(Cart $cart, int $step): string
    {
        return 'cart:'.$cart->id.':step'.$step;
    }
}

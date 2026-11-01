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
 * Two steps, timed in admin (default 1 h and 24 h after the bag went idle).
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

        $first = $settings->number('timing.abandoned_first_hours');
        $steps = [1 => $first, 2 => max($first + 1, $settings->number('timing.abandoned_second_hours'))];

        $dryRun = (bool) $this->option('dry-run');
        $budget = $guard->remainingToday();
        $queued = 0;

        Cart::query()
            ->where('is_active', 1)
            ->where('items_count', '>', 0)
            ->whereNotNull('customer_email')
            ->where('updated_at', '>=', now()->subHours($steps[2] + (int) config('notifications.abandoned_cart.grace_hours', 48)))
            ->where('updated_at', '<=', now()->subHours($steps[1]))
            ->orderBy('updated_at')
            ->each(function (Cart $cart) use ($dispatcher, $guard, $steps, $dryRun, &$budget, &$queued) {
                if ($budget <= 0) {
                    return false;
                }

                $step = $this->dueStep($cart, $steps, $dispatcher);

                if (! $step || $this->hasOrdered($cart)) {
                    return;
                }

                /* This bag's own earlier reminder never blocks its next one. */
                if ($reason = $guard->blockedReason($cart->customer_email, $this->sequence($cart))) {
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
                        ['cartId' => $cart->id],
                    ),
                    URL::signedRoute('shop.notifications.unsubscribe', [
                        'email' => mb_strtolower($cart->customer_email),
                        'scope' => EmailSuppression::SCOPE_MARKETING,
                    ]),
                );

                if ($dispatcher->send($mail, 'marketing.abandoned_cart', $this->sequence($cart).$step, $cart, EmailLog::CATEGORY_MARKETING)) {
                    $budget--;
                    $queued++;
                }
            });

        $this->info(($dryRun ? 'Dry run, nothing sent. Would queue' : 'Queued')." {$queued} reminder(s).");

        return self::SUCCESS;
    }

    /**
     * The latest step whose idle time has passed and which this bag has not
     * had yet. A bag found late gets only its latest step, never two at once.
     */
    protected function dueStep(Cart $cart, array $steps, EmailDispatcher $dispatcher): ?int
    {
        $idleHours = $cart->updated_at->diffInHours(now());

        foreach (array_reverse($steps, true) as $step => $hours) {
            if ($idleHours < $hours) {
                continue;
            }

            return $dispatcher->alreadySent($this->sequence($cart).$step) ? null : (int) $step;
        }

        return null;
    }

    protected function hasOrdered(Cart $cart): bool
    {
        return Order::where('cart_id', $cart->id)->exists()
            || Order::where('customer_email', $cart->customer_email)->where('created_at', '>=', $cart->created_at)->exists();
    }

    protected function sequence(Cart $cart): string
    {
        return 'cart:'.$cart->id.':step';
    }
}

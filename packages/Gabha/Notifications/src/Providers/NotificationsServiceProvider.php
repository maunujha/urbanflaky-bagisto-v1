<?php

namespace Gabha\Notifications\Providers;

use Gabha\Notifications\Console\SendAbandonedCartReminders;
use Gabha\Notifications\Console\SendReviewRequests;
use Gabha\Notifications\Console\SendWinBackEmails;
use Gabha\Notifications\Listeners\LogEmailDelivery;
use Gabha\Notifications\Listeners\OrderEmails;
use Gabha\Notifications\Listeners\SendShipmentStageEmail;
use Gabha\Notifications\Models\EmailLog;
use Gabha\Notifications\Services\OrderConfirmationCoordinator;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Email automation: order confirmation with invoice, courier updates,
 * review requests, abandoned bag and win-back emails, and a delivery log.
 * Every automated email goes through Services\EmailDispatcher.
 */
class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/notifications.php', 'notifications');
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/system.php', 'core');
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');

        /* Holds the orders placed in this request until it ends. */
        $this->app->singleton(OrderConfirmationCoordinator::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__).'/Database/Migrations');
        $this->loadRoutesFrom(dirname(__DIR__).'/Routes/admin-routes.php');
        $this->loadRoutesFrom(dirname(__DIR__).'/Routes/shop-routes.php');
        $this->loadViewsFrom(dirname(__DIR__).'/Resources/views', 'notifications');
        $this->loadTranslationsFrom(dirname(__DIR__).'/Resources/lang', 'notifications');

        Event::listen('checkout.order.save.after', [OrderEmails::class, 'orderPlaced']);
        Event::listen('sales.invoice.save.after', [OrderEmails::class, 'invoiceCreated']);
        Event::listen('shiprocket.shipment.stage_changed', [SendShipmentStageEmail::class, 'handle']);
        Event::listen(MessageSending::class, [LogEmailDelivery::class, 'sending']);
        Event::listen(MessageSent::class, [LogEmailDelivery::class, 'sent']);
        Event::listen(JobFailed::class, [LogEmailDelivery::class, 'failed']);

        if ($this->app->runningInConsole()) {
            $this->commands([
                SendAbandonedCartReminders::class,
                SendReviewRequests::class,
                SendWinBackEmails::class,
            ]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('model:prune', ['--model' => [EmailLog::class]])->weekly();

            if (! config('notifications.scheduled')) {
                return;
            }

            $schedule->command('notifications:abandoned-carts')->hourlyAt(15)->withoutOverlapping();
            $schedule->command('notifications:review-requests')->dailyAt('11:00')->withoutOverlapping();
            $schedule->command('notifications:win-back')->weeklyOn(2, '12:00')->withoutOverlapping();
        });
    }
}

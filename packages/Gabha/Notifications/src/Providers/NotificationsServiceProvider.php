<?php

namespace Gabha\Notifications\Providers;

use Gabha\Notifications\Console\SendAbandonedCartReminders;
use Gabha\Notifications\Console\SendBackInStockAlerts;
use Gabha\Notifications\Console\SendFeedbackRequests;
use Gabha\Notifications\Console\SendReviewRequests;
use Gabha\Notifications\Console\SendWinBackEmails;
use Gabha\Notifications\Listeners\LogEmailDelivery;
use Gabha\Notifications\Listeners\OrderEmails;
use Gabha\Notifications\Listeners\SendShipmentStageEmail;
use Gabha\Notifications\Listeners\SubscriptionEmails;
use Gabha\Notifications\Models\EmailLog;
use Gabha\Notifications\Observers\SubscriberConsentObserver;
use Gabha\Notifications\Services\OrderConfirmationCoordinator;
use Gabha\Notifications\Services\SafeCampaignSender;
use Gabha\Notifications\Services\WelcomeOffer;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Webkul\Core\Models\SubscribersListProxy;
use Webkul\Marketing\Helpers\Campaign;

/**
 * Email automation: order confirmation with invoice, courier updates,
 * lifecycle emails (bag reminders, reviews, feedback, win-back, back in
 * stock), safe campaigns, consent ledger and a delivery log. Every automated
 * email goes through Services\EmailDispatcher.
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

        /* Bagisto's `campaign:process` resolves this helper: send campaigns safely instead. */
        $this->app->bind(Campaign::class, SafeCampaignSender::class);
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
        Event::listen('customer.subscription.confirmation-requested', [SubscriptionEmails::class, 'confirmationRequested']);
        Event::listen(MessageSending::class, [LogEmailDelivery::class, 'sending']);
        Event::listen(MessageSent::class, [LogEmailDelivery::class, 'sent']);
        Event::listen(JobFailed::class, [LogEmailDelivery::class, 'failed']);

        SubscribersListProxy::modelClass()::observe(SubscriberConsentObserver::class);

        /* The welcome offer joins the existing welcome and newsletter emails. */
        View::composer([
            'shop::emails.customers.registration',
            'shop::emails.customers.subscribed',
            'shop::emails.customers.subscribed-text',
        ], fn ($view) => $view->with('welcomeOffer', app(WelcomeOffer::class)->current()));

        /* "Email me when it's back" under the product page's delivery checker. */
        Event::listen('bagisto.shop.products.view.additional_actions.before', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('notifications::shop.stock-alert');
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                SendAbandonedCartReminders::class,
                SendReviewRequests::class,
                SendFeedbackRequests::class,
                SendWinBackEmails::class,
                SendBackInStockAlerts::class,
            ]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('model:prune', ['--model' => [EmailLog::class]])->weekly();

            if (! config('notifications.scheduled')) {
                return;
            }

            $schedule->command('notifications:abandoned-carts')->everyFifteenMinutes()->withoutOverlapping();
            $schedule->command('notifications:back-in-stock')->everyFifteenMinutes()->withoutOverlapping();
            $schedule->command('notifications:review-requests')->dailyAt('11:00')->withoutOverlapping();
            $schedule->command('notifications:feedback-requests')->dailyAt('11:30')->withoutOverlapping();
            $schedule->command('notifications:win-back')->dailyAt('12:00')->withoutOverlapping();
        });
    }
}

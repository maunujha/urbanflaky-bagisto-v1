<?php

namespace Webkul\Razorpay\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Webkul\Razorpay\Listeners\Refund;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        /**
         * Refund the money at the gateway when an admin creates a credit-memo.
         */
        'sales.refund.save.after' => [
            [Refund::class, 'refundPayment'],
        ],
    ];
}

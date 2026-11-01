<?php

/*
| Email automation defaults. On/off switches for each automated email live in
| Admin → Configure → Email → Automated Emails (config/system.php); the keys
| below are the fallbacks used before that screen is first saved, plus the
| sending limits, which deliberately stay out of the admin UI.
*/

return [
    /*
     * Queue every automated email goes through. Keep "default" until the
     * Supervisor worker also listens on a dedicated queue (see DEPLOYMENT.md).
     */
    'queue' => env('MAIL_QUEUE', 'default'),

    /*
     * Whether the scheduler runs the review, abandoned-bag and win-back
     * commands. Off outside production so a dev machine with real SMTP
     * credentials never mails customers on its own.
     */
    'scheduled' => (bool) env('MAIL_AUTOMATION_SCHEDULED', env('APP_ENV') === 'production'),

    /*
     * Payment methods settled outside the store: their orders never get a
     * "payment received" email, and their invoice is never folded into the
     * order confirmation.
     */
    'offline_payment_methods' => ['cashondelivery', 'moneytransfer'],

    /*
     * The "being packed" email is skipped when the courier label comes this
     * soon after the order: the confirmation has only just gone out.
     */
    'processing_min_age_minutes' => 60,

    /* Defaults for the admin switches (used until the config page is saved). */
    'defaults' => [
        'shipping' => [
            'processing'       => true,
            'out_for_delivery' => true,
            'delivered'        => true,
            'delivery_failed'  => true,
            'rto'              => true,
        ],
        'lifecycle' => [
            'review_request' => true,
            'abandoned_cart' => true,
            'win_back'       => true,
        ],
    ],

    'review_request' => [
        /* Days after delivery before asking for a review. */
        'delay_days' => (int) env('MAIL_REVIEW_DELAY_DAYS', 5),

        /* Orders delivered longer ago than delay + this are never asked. */
        'window_days' => 3,
    ],

    'abandoned_cart' => [
        /*
         * Hours of inactivity before each reminder; no step after the last.
         * The 48-hour marketing gap also applies between the two.
         */
        'steps' => [1 => 1, 2 => 48],

        /* Carts idle for longer than this are left alone. */
        'max_age_hours' => 96,

        /* Days the recovery link stays valid. */
        'link_days' => 7,
    ],

    'win_back' => [
        /* Customers whose last order is between these many days old. */
        'min_days' => 90,
        'max_days' => 365,
    ],

    /*
     * Hostinger relays through MailChannels, which blocks senders that spike.
     * Marketing mail (abandoned cart, win-back) is capped hard; transactional
     * mail is never capped.
     */
    'limits' => [
        'marketing_daily_cap'      => (int) env('MAIL_MARKETING_DAILY_CAP', 100),
        'marketing_gap_hours'      => (int) env('MAIL_MARKETING_GAP_HOURS', 48),
        'review_daily_cap'         => (int) env('MAIL_REVIEW_DAILY_CAP', 100),
    ],

    /* Email log rows older than this are pruned. */
    'log_retention_days' => 180,
];

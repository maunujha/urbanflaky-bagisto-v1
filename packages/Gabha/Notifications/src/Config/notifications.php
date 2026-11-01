<?php

/*
| Email automation defaults. Switches, timings and the welcome offer live in
| Admin → Configure → Email → Automated Emails (Config/system.php); the
| `defaults` below apply until that screen is first saved. Sending limits stay
| out of the admin UI on purpose.
*/

return [
    /*
     * Queue every automated email goes through. Keep "default" until the
     * Supervisor worker also listens on a dedicated queue (see DEPLOYMENT.md).
     */
    'queue' => env('MAIL_QUEUE', 'default'),

    /*
     * Whether the scheduler runs the lifecycle commands (reminders, reviews,
     * feedback, win-back, back-in-stock, campaigns). Off outside production so
     * a dev machine with real SMTP credentials never mails customers on its own.
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

    /* Values used until the admin page is saved (keys mirror Config/system.php). */
    'defaults' => [
        'shipping' => [
            'processing'       => true,
            'out_for_delivery' => true,
            'delivered'        => true,
            'delivery_failed'  => true,
            'rto'              => true,
        ],
        'lifecycle' => [
            'review_request'   => true,
            'feedback_request' => false,
            'abandoned_cart'   => true,
            'win_back'         => true,
            'back_in_stock'    => true,
            'campaigns'        => true,
        ],
        'timing' => [
            'abandoned_first_hours'  => 1,
            'abandoned_second_hours' => 24,
            'review_delay_days'      => 3,
            'feedback_delay_days'    => 7,
            'win_back_min_days'      => 45,
            'win_back_max_days'      => 60,
        ],
        'welcome' => [
            'offer_enabled' => false,
            'offer_coupon'  => '',
            'offer_text'    => '',
            'double_opt_in' => false,
        ],
    ],

    'review_request' => [
        /* Orders delivered longer ago than the delay plus this are never asked. */
        'window_days' => 3,
    ],

    'abandoned_cart' => [
        /* Carts idle this long after the last reminder is due are left alone. */
        'grace_hours' => 48,

        /* Days the recovery link stays valid. */
        'link_days' => 7,
    ],

    'back_in_stock' => [
        /* Requests older than this expire unsent. */
        'expire_days' => 90,

        /* Emails per run, so a big restock is spread over several runs. */
        'per_run' => 50,
    ],

    'campaigns' => [
        /* A dated campaign keeps sending (within the cap) this many days after its date. */
        'spillover_days' => 3,
    ],

    /*
     * Hostinger relays through MailChannels, which blocks senders that spike.
     * Marketing mail (abandoned bag, win-back, campaigns) is capped hard;
     * transactional mail is never capped.
     */
    'limits' => [
        'marketing_daily_cap' => (int) env('MAIL_MARKETING_DAILY_CAP', 100),
        'marketing_gap_hours' => (int) env('MAIL_MARKETING_GAP_HOURS', 48),
        'campaign_daily_cap'  => (int) env('MAIL_CAMPAIGN_DAILY_CAP', 200),
        'review_daily_cap'    => (int) env('MAIL_REVIEW_DAILY_CAP', 100),
    ],

    /* Email log rows older than this are pruned. */
    'log_retention_days' => 180,

    /*
     * Shown in every email's footer (shop::emails.layout). The support email
     * and phone come from admin config (Email → Contact, Shipping → Origin).
     */
    'brand' => [
        'tagline'       => 'Premium essentials',
        'company'       => 'A brand by Gabha Enterprise · Dholpur, Rajasthan, India',
        'support_hours' => 'Mon – Sat, 10am – 7pm IST',
        'social'        => [
            'Instagram' => 'https://instagram.com/urbanflaky',
            'Facebook'  => 'https://facebook.com/urbanflaky',
            'X'         => 'https://x.com/urbanflaky',
        ],
    ],
];

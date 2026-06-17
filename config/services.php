<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'exchange_api' => [
        'default' => 'exchange_rates',

        'fixer' => [
            'key' => env('FIXER_API_KEY'),
            'class' => 'Webkul\Core\Helpers\Exchange\FixerExchange',
        ],

        'exchange_rates' => [
            'key' => env('EXCHANGE_RATES_API_KEY'),
            'class' => 'Webkul\Core\Helpers\Exchange\ExchangeRates',
        ],
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_CALLBACK_URL'),
    ],

    'twitter' => [
        'client_id' => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
        'redirect' => env('TWITTER_CALLBACK_URL'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_CALLBACK_URL'),
    ],

    'linkedin-openid' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('LINKEDIN_CALLBACK_URL'),
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_CALLBACK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics & Tag Management
    |--------------------------------------------------------------------------
    |
    | IDs are committed here as defaults so tracking works the moment the code
    | is deployed (the .env file is git-ignored and never reaches the server).
    | Any environment can still override via .env, and emptying the value acts
    | as a kill-switch — the corresponding <script> is not rendered at all.
    |
    */

    'gtm' => [
        // Google Tag Manager container — fires GA4, Meta Pixel, etc. from one place.
        'container_id' => env('GTM_CONTAINER_ID', 'GTM-TK3MV6Q3'),
    ],

    'clarity' => [
        // Microsoft Clarity — session recordings, heatmaps, rage/dead clicks.
        'project_id' => env('CLARITY_PROJECT_ID', 'x80ym5sh5u'),
    ],

    'smsalert' => [
        'username'    => env('SMSALERT_USERNAME'),
        'apikey'      => env('SMSALERT_APIKEY'),
        'sender'      => env('SMSALERT_SENDER', 'GABHAE'),
        'otp_expiry'  => env('OTP_EXPIRY_MINUTES', 10),
        'admin_phone'             => env('ADMIN_PHONE'),
        'tpl_otp'                 => env('SMSALERT_TEMPLATE_OTP'),
        'tpl_welcome'             => env('SMSALERT_TEMPLATE_WELCOME'),
        'tpl_registration'        => env('SMSALERT_TEMPLATE_REGISTRATION'),
        'tpl_order_placed'        => env('SMSALERT_TEMPLATE_ORDER_PLACED'),
        'tpl_order_shipped'       => env('SMSALERT_TEMPLATE_ORDER_SHIPPED'),
        'tpl_order_delivered'     => env('SMSALERT_TEMPLATE_ORDER_DELIVERED'),
        'tpl_order_cancelled'     => env('SMSALERT_TEMPLATE_ORDER_CANCELLED'),
        'tpl_order_refunded'      => env('SMSALERT_TEMPLATE_ORDER_REFUNDED'),
        'tpl_refund_processed'    => env('SMSALERT_TEMPLATE_REFUND_PROCESSED'),
        'tpl_abandoned_cart'      => env('SMSALERT_TEMPLATE_ABANDONED_CART'),
        'tpl_admin_inquiry'       => env('SMSALERT_TEMPLATE_ADMIN_INQUIRY'),
        'tpl_admin_signup'        => env('SMSALERT_TEMPLATE_ADMIN_SIGNUP'),
        'tpl_admin_order_status'  => env('SMSALERT_TEMPLATE_ADMIN_ORDER_STATUS'),
        'tpl_admin_new_order'     => env('SMSALERT_TEMPLATE_ADMIN_NEW_ORDER'),
    ],
];

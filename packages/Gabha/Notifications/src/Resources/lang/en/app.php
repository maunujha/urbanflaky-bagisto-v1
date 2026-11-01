<?php

return [
    'emails' => [
        'hello'     => 'Hello :name,',
        'order-ref' => 'Order #:order_id',

        'order' => [
            'paid-subject' => 'Payment received: order #:order_id is confirmed',
            'cod-subject'  => 'Order #:order_id confirmed: cash on delivery',
        ],

        'shipment' => [
            'processing' => [
                'subject' => 'Your order #:order_id is being packed',
                'title'   => 'Being packed',
                'body'    => 'Your order is being packed and labelled for the courier. We will email you again the moment it is on its way.',
            ],
            'out_for_delivery' => [
                'subject' => 'Arriving today: order #:order_id',
                'title'   => 'Out for delivery',
                'body'    => 'Your order is with the courier and arriving today. Keep your phone close; the delivery partner may call before reaching you.',
                'cod'     => 'This is a cash on delivery order. Please keep :amount ready.',
            ],
            'delivered' => [
                'subject' => 'Delivered: order #:order_id',
                'title'   => 'Delivered',
                'body'    => 'Your order has been delivered. We hope it feels as good as it looks. If anything is not right, reply to this email and we will make it right.',
            ],
            'delivery_failed' => [
                'subject' => 'We missed you: order #:order_id',
                'title'   => 'Delivery attempted',
                'body'    => 'The courier tried to deliver your order but could not complete it. They will try again. If your address or phone number needs a correction, reply to this email today.',
            ],
            'rto' => [
                'subject' => 'Your order is returning to us: #:order_id',
                'title'   => 'Returning to us',
                'body'    => 'After repeated attempts the courier could not deliver your order, so it is on its way back to us. Reply to this email if you would still like it; we will help you place it again.',
            ],
            'track' => 'Track your order',
            'view'  => 'View order',
        ],

        'review' => [
            'subject' => 'How does it fit?',
            'title'   => 'How does it fit?',
            'body'    => 'Your order arrived a few days ago. A short review helps the next person choose the right size, and tells us what to keep making.',
            'cta'     => 'Review',
            'opt-out' => 'Stop review requests',
            'why'     => 'You are receiving this because you ordered from Urbanflaky.',
        ],

        'cart' => [
            'step1' => [
                'subject' => 'Your bag is still waiting',
                'title'   => 'Your bag is still waiting',
                'body'    => 'You left a few pieces in your bag. We have kept them for you, though sizes in a drop do not wait long.',
            ],
            'step2' => [
                'subject' => 'Still thinking it over?',
                'title'   => 'Still thinking it over?',
                'body'    => 'Your selection is still in your bag. If sizing is the question, our size guide and support team are a reply away.',
            ],
            'payment' => [
                'subject' => 'Your payment did not go through',
                'title'   => 'Your payment did not go through',
                'body'    => 'Your order was not placed because the payment was not completed. No money was taken; if your bank shows a debit, it is reversed automatically. Your bag is saved, so you can try again in a minute.',
            ],
            'cta'       => 'Return to your bag',
            'qty'       => 'Qty :qty',
            'qty-price' => 'Qty :qty × :price',
            'total'     => 'Bag total',
        ],

        'welcome-offer' => [
            'default' => 'A welcome from us, for your first order.',
            'code'    => 'Your code',
        ],

        'confirm-subscription' => [
            'subject' => 'Confirm your email for Urbanflaky',
            'title'   => 'One click to confirm',
            'body'    => 'Confirm this address to receive new drops, restocks and early access. You can unsubscribe at any time.',
            'cta'     => 'Confirm my email',
            'ignore'  => 'Did not sign up? Ignore this email and you will not hear from us.',
        ],

        'feedback' => [
            'subject' => 'How did we do?',
            'title'   => 'How did we do?',
            'body'    => 'Your order #:order_id arrived a week ago. One tap tells us how the whole experience felt, from ordering to wearing.',
            'rate'    => 'Rate :rating out of 5',
            'scale'   => '1 = poor, 5 = excellent',
        ],

        'back-in-stock' => [
            'subject' => 'Back in stock: :product',
            'title'   => 'It is back',
            'body'    => 'The piece you asked about is available again. Sizes in a restock go quickly.',
            'cta'     => 'Shop it now',
            'why'     => 'You asked us to email you when this was back. We will not email you about it again.',
        ],

        'win-back' => [
            'subject' => 'New in at Urbanflaky',
            'title'   => 'It has been a while',
            'body'    => 'Since your last order we have added new heavyweight cotton, new oversized fits and new colourways in black, white and grey. Take a look at what is new.',
            'cta'     => 'See what is new',
        ],

        'unsubscribe'  => 'Unsubscribe',
        'why-marketing' => 'You are receiving this because you subscribed to Urbanflaky emails.',
    ],

    'preferences' => [
        'title'        => 'Email preferences',
        'confirm'      => 'Stop these emails for :email?',
        'scopes'       => [
            'reviews'   => 'Review and feedback requests after delivery',
            'marketing' => 'Bag reminders, new collections and promotions',
        ],
        'note'         => 'Order, payment and delivery emails are not affected.',
        'button'       => 'Unsubscribe',
        'done'         => 'Done. You will not receive these emails again.',
        'invalid'      => 'This link has expired or is invalid.',
    ],

    'feedback' => [
        'title'    => 'Rate your order',
        'question' => 'How was your experience overall?',
        'comment'  => 'Anything you would like to add? (optional)',
        'send'     => 'Send',
        'thanks'   => 'Thank you. Every answer is read by the team.',
    ],

    'stock' => [
        'title-size'    => 'Size sold out?',
        'title-product' => 'Sold out',
        'info'          => 'Leave your email and we will write once, the moment it is back.',
        'size'          => 'Size',
        'email'         => 'Email address',
        'cta'           => 'Notify me',
        'saved'         => 'Done. We will email you when it is back.',
        'in-stock'      => 'Good news: this is in stock right now.',
        'invalid-email' => 'Please enter a valid email address.',
        'verify-failed' => 'We could not verify the request. Please refresh the page and try again.',
        'unavailable'   => 'Restock alerts are not available right now.',
        'error'         => 'Something went wrong. Please try again.',
    ],

    'admin' => [
        'feedback' => [
            'menu'    => 'Customer Feedback',
            'title'   => 'Customer Feedback',
            'info'    => 'Ratings from the feedback email sent after delivery (switch it on under Configure → Email → Automated Emails).',
            'average' => 'Average rating',
            'count'   => 'Responses',
            'empty'   => 'No feedback yet.',
            'columns' => [
                'date'     => 'Date',
                'order'    => 'Order',
                'customer' => 'Customer',
                'rating'   => 'Rating',
                'comment'  => 'Comment',
            ],
        ],

        'menu'  => 'Email Log',
        'acl'   => 'Email Log',
        'title' => 'Email Log',
        'info'  => 'Every email the store queues or sends, with its delivery status. Previews render with recent data and never send.',
        'filters' => [
            'all-status' => 'All statuses',
            'all-types'  => 'All types',
            'search'     => 'Search recipient (full email shows consent)',
            'apply'      => 'Filter',
        ],
        'statuses' => [
            'queued'     => 'Queued',
            'sent'       => 'Sent',
            'failed'     => 'Failed',
            'suppressed' => 'Suppressed',
        ],
        'retry' => [
            'acl'          => 'Retry failed emails',
            'button'       => 'Retry',
            'queued'       => 'The email is queued again.',
            'unavailable'  => 'This email cannot be retried (its failed job is no longer kept, or it is not failed).',
            'already-sent' => 'Not retried: this email has already gone out since.',
        ],
        'consent' => [
            'title'        => 'Marketing consent',
            'subscribed'   => 'Newsletter: subscribed',
            'unsubscribed' => 'Newsletter: not subscribed',
            'suppressed'   => 'Opted out of: :scopes',
            'history'      => 'Consent history',
            'none'         => 'No consent changes recorded.',
        ],
        'columns' => [
            'date'      => 'Date',
            'type'      => 'Type',
            'recipient' => 'Recipient',
            'subject'   => 'Subject',
            'status'    => 'Status',
        ],
        'stats' => [
            'sent-today'      => 'Sent today',
            'failed-week'     => 'Failed (7 days)',
            'marketing-today' => 'Marketing today',
        ],
        'templates-hint' => 'Preview any customer email, edit its subject or send yourself a test from Customer Emails.',
        'no-preview' => 'No real record to build this email from yet (it appears once the first matching order, return or customer exists).',

        'templates' => [
            'menu'            => 'Customer Emails',
            'title'           => 'Customer Emails',
            'info'            => 'Every email customers receive. Open one to edit its subject, preview it with the latest real order, return or customer, and send yourself a test.',
            'default-subject' => 'Built-in subject',
            'custom'          => 'Custom',
            'open'            => 'Open',
            'subject'         => 'Subject',
            'subject-help'    => 'Leave empty for the built-in subject (which already differs for prepaid and cash-on-delivery orders). A custom subject replaces it for every variant. Placeholders:',
            'save'            => 'Save subject',
            'saved'           => 'Subject saved.',
            'test'            => 'Send a test',
            'test-help'       => 'Sends this preview, marked [Test], to this address only. :left of :limit test sends left this hour.',
            'send-test'       => 'Send test email',
            'test-sent'       => 'Test email sent to :email.',
            'test-failed'     => 'The test email could not be sent. See the Email Log for the error.',
            'test-invalid'    => 'Enter a real email address.',
            'test-limit'      => 'Test limit reached (:limit per hour). Try again later.',
            'preview-note'    => 'Rendered with the latest real record. Nothing is sent.',
            'plain-text'      => 'Plain-text version',
        ],
        'empty'      => 'No emails logged yet.',
    ],

    'system' => [
        'title'        => 'Automated Emails',
        'info'         => 'Emails the store sends on its own after checkout. Order, invoice, shipment and refund emails are under Notifications.',
        'shipping'     => 'Delivery Updates',
        'shipping-info' => 'Sent from Shiprocket tracking events.',
        'lifecycle'    => 'Follow-ups',
        'lifecycle-info' => 'Bag reminders, win-back and campaigns go only to newsletter subscribers, at most one every 48 hours, within daily caps. Review and feedback requests need a Shiprocket-confirmed delivery.',
        'timing'       => 'Timing',
        'timing-info'  => 'Whole numbers. Bag reminders count hours since the bag was last changed; review and feedback count days since delivery; win-back counts days since the last order.',
        'welcome'      => 'Welcome & Newsletter',
        'welcome-info' => 'The offer appears in the welcome and newsletter emails only while its coupon exists, is active and has uses left. Double opt-in makes new newsletter sign-ups confirm by email first.',
        'fields' => [
            'processing'             => 'Being packed (courier label created)',
            'out_for_delivery'       => 'Out for delivery',
            'delivered'              => 'Delivered',
            'delivery_failed'        => 'Delivery attempt failed',
            'rto'                    => 'Returning to us (RTO)',
            'review_request'         => 'Review request after delivery',
            'feedback_request'       => 'Experience rating request after delivery',
            'abandoned_cart'         => 'Abandoned bag and failed payment reminders',
            'win_back'               => 'Win-back for lapsed customers',
            'back_in_stock'          => 'Back-in-stock alerts (product page form + email)',
            'campaigns'              => 'Campaigns (Marketing → Campaigns)',
            'abandoned_first_hours'  => 'First bag reminder after (hours)',
            'abandoned_second_hours' => 'Second bag reminder after (hours)',
            'review_delay_days'      => 'Review request after delivery (days)',
            'feedback_delay_days'    => 'Rating request after delivery (days)',
            'win_back_min_days'      => 'Win-back from (days since last order)',
            'win_back_max_days'      => 'Win-back until (days since last order)',
            'offer_enabled'          => 'Show welcome offer',
            'offer_coupon'           => 'Welcome offer coupon code',
            'offer_text'             => 'Welcome offer line (e.g. "10% off your first order")',
            'double_opt_in'          => 'Newsletter double opt-in',
        ],
    ],
];

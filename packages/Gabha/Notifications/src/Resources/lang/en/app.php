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
            'cta' => 'Return to your bag',
            'qty' => 'Qty :qty',
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
            'reviews'   => 'Review requests after delivery',
            'marketing' => 'Bag reminders and new-arrival emails',
        ],
        'note'         => 'Order, payment and delivery emails are not affected.',
        'button'       => 'Unsubscribe',
        'done'         => 'Done. You will not receive these emails again.',
        'invalid'      => 'This link has expired or is invalid.',
    ],

    'admin' => [
        'menu'  => 'Email Log',
        'acl'   => 'Email Log',
        'title' => 'Email Log',
        'info'  => 'Every email the store queues or sends, with its delivery status. Previews render with recent data and never send.',
        'filters' => [
            'all-status' => 'All statuses',
            'all-types'  => 'All types',
            'search'     => 'Search recipient',
            'apply'      => 'Filter',
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
        'previews'   => 'Previews',
        'no-preview' => 'No recent data to preview this email with.',
        'empty'      => 'No emails logged yet.',
    ],

    'system' => [
        'title'        => 'Automated Emails',
        'info'         => 'Emails the store sends on its own after checkout. Order, invoice, shipment and refund emails are under Notifications.',
        'shipping'     => 'Delivery Updates',
        'shipping-info' => 'Sent from Shiprocket tracking events.',
        'lifecycle'    => 'Follow-ups',
        'lifecycle-info' => 'Bag reminders and win-back go only to newsletter subscribers, at most one every 48 hours, within a daily cap.',
        'fields' => [
            'processing'       => 'Being packed (courier label created)',
            'out_for_delivery' => 'Out for delivery',
            'delivered'        => 'Delivered',
            'delivery_failed'  => 'Delivery attempt failed',
            'rto'              => 'Returning to us (RTO)',
            'review_request'   => 'Review request after delivery',
            'abandoned_cart'   => 'Abandoned bag and failed payment reminders',
            'win_back'         => 'Win-back for lapsed customers',
        ],
    ],
];

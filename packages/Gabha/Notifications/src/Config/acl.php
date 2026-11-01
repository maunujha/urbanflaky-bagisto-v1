<?php

/* Same keys as the menu items: the sidebar hides items the role cannot access. */
return [
    [
        'key'   => 'marketing.communications.customer_emails',
        'name'  => 'notifications::app.admin.templates.menu',
        'route' => 'admin.notifications.templates.index',
        'sort'  => 4,
    ], [
        'key'   => 'marketing.communications.email_log',
        'name'  => 'notifications::app.admin.acl',
        'route' => 'admin.notifications.logs.index',
        'sort'  => 5,
    ], [
        'key'   => 'marketing.communications.email_log.retry',
        'name'  => 'notifications::app.admin.retry.acl',
        'route' => 'admin.notifications.logs.retry',
        'sort'  => 1,
    ], [
        'key'   => 'marketing.communications.customer_feedback',
        'name'  => 'notifications::app.admin.feedback.menu',
        'route' => 'admin.notifications.feedback.index',
        'sort'  => 6,
    ],
];

<?php

/* Same key as the menu item: the sidebar hides items the role cannot access. */
return [
    [
        'key'   => 'marketing.communications.email_log',
        'name'  => 'notifications::app.admin.acl',
        'route' => 'admin.notifications.logs.index',
        'sort'  => 5,
    ],
];

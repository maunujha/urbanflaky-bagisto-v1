<?php

return [
    [
        'key'   => 'lookbook',
        'name'  => 'lookbook::app.admin.acl.lookbook',
        'route' => 'admin.lookbook.index',
        'sort'  => 4,
    ], [
        'key'   => 'lookbook.view',
        'name'  => 'lookbook::app.admin.acl.view',
        'route' => 'admin.lookbook.index',
        'sort'  => 1,
    ], [
        'key'   => 'lookbook.create',
        'name'  => 'lookbook::app.admin.acl.create',
        'route' => 'admin.lookbook.create',
        'sort'  => 2,
    ], [
        'key'   => 'lookbook.edit',
        'name'  => 'lookbook::app.admin.acl.edit',
        'route' => 'admin.lookbook.edit',
        'sort'  => 3,
    ], [
        'key'   => 'lookbook.delete',
        'name'  => 'lookbook::app.admin.acl.delete',
        'route' => 'admin.lookbook.delete',
        'sort'  => 4,
    ],
];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Blog ACL (nested under the existing CMS ACL group)
    |--------------------------------------------------------------------------
    |
    | Keys are nested under "cms" so they line up with the sidebar menu keys
    | (cms.blogs). The menu builder filters items by bouncer()->hasPermission(),
    | so the menu key MUST equal the ACL key for non-super-admin roles to see
    | the items.
    |
    */
    [
        'key'   => 'cms.blogs',
        'name'  => 'blog::app.acl.blogs',
        'route' => 'admin.blogs.index',
        'sort'  => 4,
    ], [
        'key'   => 'cms.blogs.create',
        'name'  => 'blog::app.acl.create',
        'route' => 'admin.blogs.create',
        'sort'  => 1,
    ], [
        'key'   => 'cms.blogs.edit',
        'name'  => 'blog::app.acl.edit',
        'route' => 'admin.blogs.edit',
        'sort'  => 2,
    ], [
        'key'   => 'cms.blogs.delete',
        'name'  => 'blog::app.acl.delete',
        'route' => 'admin.blogs.delete',
        'sort'  => 3,
    ],
];

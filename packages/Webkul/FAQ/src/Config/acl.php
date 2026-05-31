<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FAQ (nested under the existing CMS ACL group)
    |--------------------------------------------------------------------------
    |
    | Keys are nested under "cms" so they line up with the sidebar menu keys
    | (cms.faqs / cms.faq_categories). The menu builder filters items by
    | bouncer()->hasPermission(menuKey), so the menu key MUST equal the ACL key
    | for non-super-admin roles to see the items.
    |
    */
    [
        'key'   => 'cms.pages',
        'name'  => 'faq::app.acl.pages',
        'route' => 'admin.cms.index',
        'sort'  => 1,
    ], [
        'key'   => 'cms.faqs',
        'name'  => 'faq::app.acl.faqs',
        'route' => 'admin.faqs.index',
        'sort'  => 2,
    ], [
        'key'   => 'cms.faqs.create',
        'name'  => 'faq::app.acl.create',
        'route' => 'admin.faqs.create',
        'sort'  => 1,
    ], [
        'key'   => 'cms.faqs.edit',
        'name'  => 'faq::app.acl.edit',
        'route' => 'admin.faqs.edit',
        'sort'  => 2,
    ], [
        'key'   => 'cms.faqs.delete',
        'name'  => 'faq::app.acl.delete',
        'route' => 'admin.faqs.delete',
        'sort'  => 3,
    ], [
        'key'   => 'cms.faq_categories',
        'name'  => 'faq::app.acl.faq-categories',
        'route' => 'admin.faqs.categories.index',
        'sort'  => 3,
    ], [
        'key'   => 'cms.faq_categories.create',
        'name'  => 'faq::app.acl.create',
        'route' => 'admin.faqs.categories.create',
        'sort'  => 1,
    ], [
        'key'   => 'cms.faq_categories.edit',
        'name'  => 'faq::app.acl.edit',
        'route' => 'admin.faqs.categories.edit',
        'sort'  => 2,
    ], [
        'key'   => 'cms.faq_categories.delete',
        'name'  => 'faq::app.acl.delete',
        'route' => 'admin.faqs.categories.delete',
        'sort'  => 3,
    ],
];

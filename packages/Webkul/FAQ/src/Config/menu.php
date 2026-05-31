<?php

return [
    /**
     * FAQ management — nested under the existing CMS menu (parent key "cms").
     *
     * NOTE: Bagisto's menu builder repoints a parent item to its FIRST child's
     * route once it gains children (see Webkul\Core\Menu::removeChildrenUnauthorizedMenuItem).
     * The core "CMS" item links to the Pages list (admin.cms.index) and had no
     * children, so adding FAQ children would hijack the CMS link and orphan Pages.
     * To preserve the original behaviour we re-register "Pages" as the first child,
     * which keeps the CMS parent pointing at the Pages list and keeps Pages reachable.
     */
    [
        'key'   => 'cms.pages',
        'name'  => 'faq::app.menu.pages',
        'route' => 'admin.cms.index',
        'sort'  => 1,
        'icon'  => '',
    ], [
        'key'   => 'cms.faqs',
        'name'  => 'faq::app.menu.faqs',
        'route' => 'admin.faqs.index',
        'sort'  => 2,
        'icon'  => '',
    ], [
        'key'   => 'cms.faq_categories',
        'name'  => 'faq::app.menu.categories',
        'route' => 'admin.faqs.categories.index',
        'sort'  => 3,
        'icon'  => '',
    ],
];

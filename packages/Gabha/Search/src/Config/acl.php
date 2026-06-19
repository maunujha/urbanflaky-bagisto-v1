<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Search Insights ACL (nested under the existing Catalog ACL group)
    |--------------------------------------------------------------------------
    |
    | Keys are nested under "catalog" so they align with the sidebar menu key
    | (catalog.search_insights). The menu builder filters items by
    | bouncer()->hasPermission(), so the menu key MUST equal the ACL key for
    | non-super-admin roles to see the item.
    |
    */
    [
        'key'   => 'catalog.search_insights',
        'name'  => 'Search Insights',
        'route' => 'admin.search.insights.index',
        'sort'  => 5,
    ], [
        'key'   => 'catalog.search_insights.delete',
        'name'  => 'Delete',
        'route' => 'admin.search.insights.mass_delete',
        'sort'  => 1,
    ],
];

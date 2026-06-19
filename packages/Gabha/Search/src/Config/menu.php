<?php

return [
    /**
     * Search Insights — a child of the existing Catalog menu (parent key
     * "catalog"), sitting after Products / Categories / Attributes / Families
     * (sorts 1–4). Only the child is declared; the Catalog parent is left
     * untouched so its link keeps pointing at the products list.
     */
    [
        'key'   => 'catalog.search_insights',
        'name'  => 'Search Insights',
        'route' => 'admin.search.insights.index',
        'sort'  => 5,
        'icon'  => '',
    ],
];

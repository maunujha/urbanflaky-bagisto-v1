<?php

return [
    /**
     * Blog management — nested under the existing CMS menu (parent key "cms").
     *
     * The FAQ package already re-registers "cms.pages" as the first child of the
     * CMS parent (which keeps the CMS parent link pointing at the Pages list).
     * We therefore only add the "cms.blogs" child here and do NOT re-declare
     * "cms.pages" — declaring it twice would create a duplicate "Pages" item.
     */
    [
        'key'   => 'cms.blogs',
        'name'  => 'blog::app.menu.blogs',
        'route' => 'admin.blogs.index',
        'sort'  => 4,
        'icon'  => '',
    ],
];

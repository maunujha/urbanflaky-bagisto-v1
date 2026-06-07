<?php

return [
    'menu' => [
        'blogs' => 'Blog',
    ],

    'acl' => [
        'blogs'  => 'Blog',
        'create' => 'Create',
        'edit'   => 'Edit',
        'delete' => 'Delete',
    ],

    'admin' => [
        'blogs' => [
            'index' => [
                'title'      => 'Blog',
                'create-btn' => 'Create Blog Post',

                'datagrid' => [
                    'id'                  => 'ID',
                    'title'               => 'Title',
                    'slug'                => 'Slug',
                    'author'              => 'Author',
                    'status'              => 'Status',
                    'published-at'        => 'Published',
                    'published'           => 'Published',
                    'draft'               => 'Draft',
                    'edit'                => 'Edit',
                    'delete'              => 'Delete',
                    'delete-success'      => 'Blog post deleted successfully.',
                    'mass-delete-success' => 'Selected blog posts deleted successfully.',
                ],
            ],

            'create' => [
                'title'             => 'Create Blog Post',
                'save-btn'          => 'Save Blog Post',
                'back-btn'          => 'Back',
                'general'           => 'Content',
                'post-title'        => 'Title',
                'post-title-placeholder' => 'e.g. 5 Ways to Style a Polo T-Shirt',
                'slug'              => 'URL Slug',
                'slug-info'         => 'Leave blank to auto-generate from the title. Lowercase letters, numbers and hyphens only.',
                'slug-placeholder'  => 'auto-generated-from-title',
                'author'            => 'Author',
                'short-description' => 'Short Description / Excerpt',
                'short-description-info' => 'Shown in the blog grid and used as the SEO description fallback.',
                'content'           => 'Content',
                'image'             => 'Featured Image',
                'image-info'        => 'Recommended 1200×630px. Used in the listing card and as the social share (Open Graph) image.',
                'publish'           => 'Publish',
                'status'            => 'Published',
                'status-info'       => 'Draft posts are hidden from the storefront.',
                'published-at'      => 'Publish Date',
                'seo'               => 'Search Engine Optimization',
                'meta-title'        => 'Meta Title',
                'meta-title-info'   => 'Defaults to the post title when left blank. Aim for under 60 characters.',
                'meta-description'  => 'Meta Description',
                'meta-description-info' => 'Defaults to the short description. Aim for 150–160 characters.',
                'meta-keywords'     => 'Meta Keywords',
                'meta-keywords-placeholder' => 'comma, separated, keywords',
            ],

            'edit' => [
                'title'    => 'Edit Blog Post',
                'save-btn' => 'Save Blog Post',
            ],

            'create-success' => 'Blog post created successfully.',
            'update-success' => 'Blog post updated successfully.',
            'delete-success' => 'Blog post deleted successfully.',
            'delete-failed'  => 'Blog post could not be deleted.',
        ],
    ],

    'shop' => [
        'title'            => 'Blog',
        'heading'          => 'The Urbanflaky Journal',
        'subheading'       => 'Style guides, fabric care and fashion notes from the Urbanflaky team.',
        'meta-title'       => 'Blog — Style Guides & Fashion Notes | Urbanflaky',
        'meta-description' => 'Read the Urbanflaky journal: polo t-shirt styling tips, slim fit care guides and everyday fashion ideas for men and women. — Gabha Enterprise',
        'read-more'        => 'Read more',
        'read-article'     => 'Read article',
        'by-author'        => 'By :author',
        'breadcrumb-home'  => 'Home',
        'breadcrumb-blog'  => 'Blog',
        'recent-posts'     => 'Recent Posts',
        'newer'            => 'Newer',
        'older'            => 'Older',
        'back-to-blog'     => 'Back to Blog',
        'no-posts'         => 'No blog posts have been published yet. Please check back soon.',
        'home-section-title'    => 'From the Journal',
        'home-section-subtitle' => 'Style guides & fashion notes from Urbanflaky',
        'view-all'         => 'View all posts',
        'share'            => 'Share',
    ],
];

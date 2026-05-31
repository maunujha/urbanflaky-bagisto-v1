<?php

return [
    'menu' => [
        'pages'      => 'Pages',
        'faqs'       => 'FAQ Management',
        'categories' => 'FAQ Categories',
    ],

    'acl' => [
        'pages'          => 'Pages',
        'faqs'           => 'FAQs',
        'faq-categories' => 'FAQ Categories',
        'create'         => 'Create',
        'edit'           => 'Edit',
        'delete'         => 'Delete',
    ],

    'admin' => [
        'faqs' => [
            'index' => [
                'title'      => 'FAQ Management',
                'create-btn' => 'Create FAQ',

                'datagrid' => [
                    'id'                  => 'ID',
                    'question'            => 'Question',
                    'category'            => 'Category',
                    'status'              => 'Status',
                    'sort-order'          => 'Sort Order',
                    'active'              => 'Active',
                    'inactive'            => 'Inactive',
                    'edit'                => 'Edit',
                    'delete'              => 'Delete',
                    'delete-success'      => 'FAQ deleted successfully.',
                    'mass-delete-success' => 'Selected FAQs deleted successfully.',
                ],
            ],

            'create' => [
                'title'        => 'Create FAQ',
                'save-btn'     => 'Save FAQ',
                'general'      => 'General',
                'question'     => 'Question',
                'answer'       => 'Answer',
                'category'     => 'Category',
                'select-category' => 'Select Category',
                'sort-order'   => 'Sort Order',
                'status'       => 'Status',
                'back-btn'     => 'Back',
            ],

            'edit' => [
                'title'    => 'Edit FAQ',
                'save-btn' => 'Save FAQ',
            ],

            'create-success' => 'FAQ created successfully.',
            'update-success' => 'FAQ updated successfully.',
            'delete-success' => 'FAQ deleted successfully.',
            'delete-failed'  => 'FAQ could not be deleted.',
        ],

        'categories' => [
            'index' => [
                'title'      => 'FAQ Categories',
                'create-btn' => 'Create Category',

                'datagrid' => [
                    'id'                  => 'ID',
                    'name'                => 'Name',
                    'slug'                => 'Slug',
                    'status'              => 'Status',
                    'sort-order'          => 'Sort Order',
                    'active'              => 'Active',
                    'inactive'            => 'Inactive',
                    'edit'                => 'Edit',
                    'delete'              => 'Delete',
                    'delete-success'      => 'Category deleted successfully.',
                    'mass-delete-success' => 'Selected categories deleted successfully.',
                ],
            ],

            'create' => [
                'title'      => 'Create FAQ Category',
                'save-btn'   => 'Save Category',
                'general'    => 'General',
                'name'       => 'Name',
                'sort-order' => 'Sort Order',
                'status'     => 'Status',
                'back-btn'   => 'Back',
            ],

            'edit' => [
                'title'    => 'Edit FAQ Category',
                'save-btn' => 'Save Category',
            ],

            'create-success'   => 'Category created successfully.',
            'update-success'   => 'Category updated successfully.',
            'delete-success'   => 'Category deleted successfully.',
            'delete-failed'    => 'Category could not be deleted.',
            'has-faqs'         => 'This category cannot be deleted because it has FAQs assigned to it.',
        ],
    ],

    'shop' => [
        'title'             => 'Frequently Asked Questions',
        'heading'           => 'Frequently Asked Questions',
        'meta-description'  => 'Find answers to common questions about orders, shipping, returns, payments, your account and product sizing at Urbanflaky.',
        'top-queries'       => 'Top Queries',
        'track-text'        => "You can track your orders in 'My Orders'.",
        'track-btn'         => 'Track Orders',
        'categories'        => 'Categories',
        'question-count'    => '{0} No questions|{1} :count question|[2,*] :count questions',
        'search-placeholder' => 'Search FAQs…',
        'searching'         => 'Searching…',
        'no-results'        => 'No Results Found',
        'no-faqs'           => 'No FAQs are available at the moment. Please check back soon.',
    ],
];

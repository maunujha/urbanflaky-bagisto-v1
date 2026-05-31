<?php

return [
    'admin' => [
        'menu' => [
            'lookbook' => 'Lookbook',
            'looks'    => 'Urbanflaky Looks',
        ],

        'acl' => [
            'lookbook' => 'Lookbook',
            'view'     => 'View',
            'create'   => 'Create',
            'edit'     => 'Edit',
            'delete'   => 'Delete',
        ],

        'datagrid' => [
            'id'            => 'ID',
            'thumbnail'     => 'Thumbnail',
            'title'         => 'Title',
            'collection'    => 'Collection',
            'type'          => 'Type',
            'image'         => 'Image',
            'reel'          => 'Reel',
            'display-order' => 'Order',
            'status'        => 'Status',
            'active'        => 'Active',
            'inactive'      => 'Inactive',
            'edit'          => 'Edit',
            'delete'        => 'Delete',
            'update-status' => 'Update Status',
        ],

        'index' => [
            'title'      => 'Urbanflaky Looks',
            'create-btn' => 'Create Look',
        ],

        'create' => [
            'title'    => 'Create Look',
            'save-btn' => 'Save Look',
            'back'     => 'Back',
        ],

        'edit' => [
            'title'    => 'Edit Look',
            'save-btn' => 'Save Look',
            'back'     => 'Back',
        ],

        'form' => [
            'general'           => 'General',
            'media'             => 'Media',
            'title'             => 'Title',
            'title-info'        => 'Internal name for this look.',
            'type'              => 'Content Type',
            'type-image'        => 'Campaign Image / Collection Photo',
            'type-reel'         => 'Reel / Video',
            'image'             => 'Thumbnail / Campaign Image',
            'image-info'        => 'Recommended portrait 4:5. Used as the card thumbnail and as the still for reels.',
            'video'             => 'Upload Video (Reel)',
            'video-info'        => 'MP4, WebM or MOV up to 50MB. Uploaded here and stored on the site — plays in the fullscreen modal. Takes priority over an external URL below.',
            'video-url'         => 'Video URL (external, optional)',
            'video-url-info'    => 'Use only if you are not uploading a file — a direct .mp4 URL or embeddable video link.',
            'permalink'         => 'Instagram Reel / Post Link',
            'permalink-info'    => 'Public link to this reel or post on Instagram. Shown as a "Watch on Instagram" button on the card and in the modal.',
            'collection-name'   => 'Collection Label',
            'collection-info'   => 'Shown on the card and in the modal, e.g. "Summer Drop", "Polo Edit".',
            'caption'           => 'Caption',
            'display-order'     => 'Display Order',
            'display-order-info'=> 'Lower numbers appear first.',
            'featured'          => 'Featured (large card)',
            'status'            => 'Active',
            'tagged-products'   => 'Tagged Products',
            'tagged-info'       => 'Search and tag the Urbanflaky products styled in this look.',
            'search-products'   => 'Search products by name…',
            'no-products'       => 'No products tagged yet.',
            'remove'            => 'Remove',
        ],

        'create-success'      => 'Look created successfully.',
        'update-success'      => 'Look updated successfully.',
        'delete-success'      => 'Look deleted successfully.',
        'mass-delete-success' => 'Selected looks deleted successfully.',
        'mass-update-success' => 'Selected looks updated successfully.',
    ],

    'shop' => [
        'title'            => 'Urbanflaky Looks',
        'subtitle'         => 'Explore the latest drops, styling inspiration and behind-the-scenes content.',
        'handle'           => '@urbanflaky',
        'view-look'        => 'View Look',
        'shop-look'        => 'Shop Look',
        'view-product'     => 'View Product',
        'shop-the-look'    => 'Shop the Look',
        'style-inspiration'=> 'Style Inspiration',
        'tagged-in'        => 'Featured products',
        'close'            => 'Close',
        'follow-instagram' => 'Follow Us On Instagram',
        'view-all-reels'   => 'View All Reels',
        'watch-on-instagram' => 'Watch on Instagram',
        'featured'         => 'Featured',
        'stats-footer'     => 'Stay updated with our latest drops, exclusives and behind the scenes.',
    ],
];

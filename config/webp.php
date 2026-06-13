<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WebP Encoding Quality
    |--------------------------------------------------------------------------
    |
    | Quality used whenever an uploaded JPG/PNG is converted to WebP
    | (product media, category images, CMS/TinyMCE uploads) and by the
    | `webp:convert` command for existing images.
    |
    */

    'quality' => (int) env('WEBP_QUALITY', 85),

    /*
    |--------------------------------------------------------------------------
    | Keep Original As Fallback
    |--------------------------------------------------------------------------
    |
    | When enabled, the uploaded original (JPG/PNG) is stored next to the
    | generated .webp with the same basename. Storefront <picture> tags use
    | it as the fallback <img> for browsers without WebP support.
    |
    */

    'keep_original' => (bool) env('WEBP_KEEP_ORIGINAL', true),

    /*
    |--------------------------------------------------------------------------
    | Fallback Extensions
    |--------------------------------------------------------------------------
    |
    | Extensions considered valid fallback siblings of a .webp file, in
    | lookup order. Only these upload types are kept as originals.
    |
    */

    'fallback_extensions' => ['jpg', 'jpeg', 'png'],

];

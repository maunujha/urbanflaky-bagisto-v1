<?php

namespace Gabha\Blog\Database\Content;

/**
 * Full editorial versions of the starter posts that originally shipped as
 * ~50-word stubs. Shared by BlogSeeder and the upgrade migration so the copy
 * lives in one place; article bodies are the sibling .html files.
 */
class Articles
{
    /**
     * Post fields keyed by slug.
     */
    public static function all(): array
    {
        return [
            'how-to-care-for-your-cotton-t-shirts-so-they-last-longer' => [
                'title'             => 'How to Care for Your Cotton T-Shirts So They Last Longer',
                'short_description' => 'Cold washes, air-drying and folding, not hanging: the simple routine that keeps heavyweight cotton tees soft, black and true to shape for years.',
                'content'           => self::body('cotton-care'),
                'meta_title'        => 'How to Care for Cotton T-Shirts So They Last | Urbanflaky',
                'meta_description'  => 'Wash, dry and store heavyweight cotton t-shirts the right way. Stop shrinkage, keep black from fading and protect oversized fits with this care routine.',
                'meta_keywords'     => 'cotton t-shirt care, heavyweight cotton, how to wash black t-shirts, oversized t-shirt care',
            ],
            'building-a-capsule-wardrobe-under-rs3000' => [
                'title'             => 'Building a Capsule Wardrobe Under ₹3000',
                'short_description' => 'Five monochrome pieces, one silhouette family and heavyweight cotton: how to build a minimal streetwear capsule that works every day, on a ₹3000 budget.',
                'content'           => self::body('capsule-wardrobe'),
                'meta_title'        => 'Capsule Wardrobe Under ₹3000: Minimal Streetwear | Urbanflaky',
                'meta_description'  => 'Build a monochrome capsule wardrobe under ₹3000 with oversized tees, one graphic piece and dark bottoms. A minimal streetwear starter guide for India.',
                'meta_keywords'     => 'capsule wardrobe, minimal streetwear, monochrome wardrobe, oversized t-shirts india',
            ],
            'the-colours-that-never-go-out-of-style' => [
                'title'             => 'The Colours That Never Go Out of Style',
                'short_description' => 'Black, white, charcoal and grey: why the monochrome palette outlasts every trend, and how to wear each shade in oversized streetwear.',
                'content'           => self::body('timeless-colours'),
                'meta_title'        => 'Timeless Colours: Black, White & Grey Style Guide | Urbanflaky',
                'meta_description'  => 'Why black, white, charcoal and grey never date, and how to wear each in a monochrome streetwear wardrobe built on oversized cotton tees.',
                'meta_keywords'     => 'monochrome fashion, timeless colours, black and white outfits, dark aesthetic fashion',
            ],
        ];
    }

    /**
     * Article HTML from the sibling file.
     */
    protected static function body(string $name): string
    {
        return trim(file_get_contents(__DIR__.'/'.$name.'.html'));
    }
}

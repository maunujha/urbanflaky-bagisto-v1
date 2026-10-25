<?php

use Gabha\Blog\Models\Blog;
use Gabha\Blog\Repositories\BlogRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Faker\Helpers\Category as CategoryFaker;

uses(DatabaseTransactions::class);

function blogPost(string $slug, string $title, int $daysAgo = 1): Blog
{
    return Blog::query()->create([
        'title'        => $title,
        'slug'         => $slug,
        'content'      => '<p>Body</p>',
        'status'       => 1,
        'published_at' => now()->subDays($daysAgo),
    ]);
}

it('ranks related posts by shared topic before recency', function () {
    Blog::query()->delete();

    $post = blogPost('black-oversized-tees', 'Black Oversized Tees', 30);
    $topical = blogPost('oversized-fits-explained', 'Oversized Fits Explained', 20);
    blogPost('newest-unrelated', 'Linen Summer Notes', 1);

    $related = app(BlogRepository::class)->getRelated($post, ['id', 'slug', 'title', 'published_at'], 1);

    expect($related->pluck('slug')->all())->toBe([$topical->slug]);
});

it('links only to active collections that exist, in topic order', function () {
    $category = (new CategoryFaker)->factory()->create(['status' => 1]);

    $slug = $category->translate(app()->getLocale())->slug;

    config(['blog.collection_links' => [
        'limit'   => 4,
        'topics'  => [['keywords' => ['oversized'], 'collections' => ['no-such-collection', $slug]]],
        'default' => [],
    ]]);

    $post = new Blog(['slug' => 'oversized-guide', 'title' => 'Oversized Guide']);

    $links = app(BlogRepository::class)->getCollectionLinks($post);

    expect($links->keys()->all())->toBe([$slug]);
});

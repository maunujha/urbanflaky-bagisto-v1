<?php

declare(strict_types=1);

namespace Gabha\Blog\ViewComposers;

use Gabha\Blog\Repositories\BlogRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Exposes the latest published blog posts to the storefront home page as
 * `$latestBlogs`, so the home grid renders server-side (crawlable, no JS)
 * without modifying the core HomeController.
 */
class HomeBlogComposer
{
    /**
     * Number of posts shown in the home grid.
     */
    const LIMIT = 4;

    /**
     * Cache key — forgotten by the Blog model whenever a post is saved or deleted.
     */
    const CACHE_KEY = 'uf_home_latest_blogs';

    public function __construct(protected BlogRepository $blogRepository) {}

    /**
     * Bind the latest posts onto the view.
     */
    public function compose(View $view): void
    {
        /* 1h TTL backstop: a scheduled `published_at` arriving between saves
           still surfaces without an explicit invalidation. */
        $blogs = Cache::remember(self::CACHE_KEY, 3600, function () {
            return $this->blogRepository
                ->getModel()
                ->newQuery()
                ->published()
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(self::LIMIT)
                ->get();
        });

        $view->with('latestBlogs', $blogs);
    }
}

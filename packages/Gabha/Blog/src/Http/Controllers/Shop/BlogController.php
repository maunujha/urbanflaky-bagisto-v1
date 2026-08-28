<?php

namespace Gabha\Blog\Http\Controllers\Shop;

use Gabha\Blog\Repositories\BlogRepository;
use Illuminate\View\View;

class BlogController
{
    /**
     * Posts shown per page on the listing.
     */
    const PER_PAGE = 9;

    /**
     * Columns the card layouts actually render. The `content` column holds the
     * full article HTML (tens of KB per row) and is never shown on a listing,
     * so it is left out of every query that only builds cards. `image` must
     * stay selected — the appended `image_url` accessor reads it.
     */
    const CARD_COLUMNS = [
        'id',
        'title',
        'slug',
        'author',
        'short_description',
        'image',
        'published_at',
    ];

    /**
     * Create a new controller instance.
     */
    public function __construct(protected BlogRepository $blogRepository) {}

    /**
     * Display the public blog listing.
     */
    public function index(): View
    {
        $blogs = $this->blogRepository
            ->getModel()
            ->newQuery()
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE, self::CARD_COLUMNS);

        return view('blog::shop.index', compact('blogs'));
    }

    /**
     * Display a single published blog post.
     */
    public function show(string $slug): View
    {
        $blog = $this->blogRepository
            ->getModel()
            ->newQuery()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $recentBlogs = $this->blogRepository
            ->getModel()
            ->newQuery()
            ->published()
            ->where('id', '!=', $blog->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get(self::CARD_COLUMNS);

        return view('blog::shop.show', compact('blog', 'recentBlogs'));
    }
}

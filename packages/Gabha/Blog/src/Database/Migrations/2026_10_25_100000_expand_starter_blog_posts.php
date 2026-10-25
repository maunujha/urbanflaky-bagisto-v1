<?php

use Gabha\Blog\Database\Content\Articles;
use Gabha\Blog\Models\Blog;
use Illuminate\Database\Migrations\Migration;

/**
 * Replace the ~50-word starter stubs with full editorial articles.
 *
 * Google reported these posts as "Discovered/Crawled – currently not indexed";
 * thin content and no internal links were the likely cause. A post is only
 * touched while it is still a stub, so edits made in the admin are never
 * overwritten.
 */
return new class extends Migration
{
    /**
     * Posts with more visible text than this have been edited and are skipped.
     */
    const STUB_MAX_WORDS = 200;

    public function up(): void
    {
        foreach (Articles::all() as $slug => $fields) {
            $blog = Blog::query()->where('slug', $slug)->first();

            if (! $blog || str_word_count(strip_tags((string) $blog->content)) > self::STUB_MAX_WORDS) {
                continue;
            }

            /* Saving through the model also clears the cached home-page grid. */
            $blog->fill($fields)->save();
        }
    }

    /**
     * Content upgrade only; the stubs are not restored.
     */
    public function down(): void {}
};

<?php

namespace Gabha\Blog\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Category\Models\CategoryTranslation;
use Webkul\Core\Eloquent\Repository;

class BlogRepository extends Repository
{
    /**
     * Words too generic to signal a shared topic between posts.
     */
    const STOP_WORDS = ['your', 'with', 'that', 'this', 'from', 'guide', 'urbanflaky', 'never', 'last', 'longer', 'they', 'style', 'under'];

    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return \Gabha\Blog\Models\Blog::class;
    }

    /**
     * Generate a URL-friendly, unique slug from the given source string.
     *
     * Uses Str::slug() and, on collision, appends an incrementing suffix
     * (-2, -3, …). When updating, pass $ignoreId so the post's own slug does
     * not count as a collision against itself.
     */
    public function generateUniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source);

        /* Fall back to a stable token when the title slugifies to nothing
         * (e.g. an all-emoji or non-latin title). */
        if ($base === '') {
            $base = 'blog-'.Str::random(6);
        }

        $slug = $base;
        $suffix = 2;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * Whether a slug is already taken (optionally excluding one post id).
     */
    protected function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return $this->model
            ->newQuery()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    /**
     * Published posts most related to the given one, ranked by shared topic words
     * across slug, title and meta keywords, newest first on ties.
     *
     * Ranking by topic (not recency) means every article — not only the three
     * newest — earns contextual internal links from its sibling posts.
     */
    public function getRelated($blog, array $columns, int $limit): Collection
    {
        $topic = $this->topicWords($blog);

        return $this->model
            ->newQuery()
            ->published()
            ->where('id', '!=', $blog->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get(array_unique([...$columns, 'meta_keywords']))
            ->sortByDesc(fn ($candidate) => count(array_intersect($topic, $this->topicWords($candidate))))
            ->take($limit)
            ->values();
    }

    /**
     * Collections to link from a post, resolved from the topic map in
     * config('blog.collection_links'). Only active categories that exist are
     * returned, keyed by slug with their display name.
     *
     * @return \Illuminate\Support\Collection<string, string> slug => name
     */
    public function getCollectionLinks($blog): Collection
    {
        $config = config('blog.collection_links');

        $haystack = Str::lower($blog->slug.' '.$blog->title.' '.$blog->meta_keywords);

        $slugs = collect($config['topics'] ?? [])
            ->filter(fn ($topic) => Str::contains($haystack, $topic['keywords']))
            ->flatMap(fn ($topic) => $topic['collections'])
            ->merge($config['default'] ?? [])
            ->unique()
            ->values();

        $names = CategoryTranslation::query()
            ->whereIn('slug', $slugs)
            ->where('locale', app()->getLocale())
            ->whereIn('category_id', fn ($query) => $query->select('id')->from('categories')->where('status', 1))
            ->pluck('name', 'slug');

        return $slugs
            ->filter(fn ($slug) => $names->has($slug))
            ->take($config['limit'] ?? 4)
            ->mapWithKeys(fn ($slug) => [$slug => $names[$slug]]);
    }

    /**
     * Significant lower-case words describing a post's topic.
     */
    protected function topicWords($blog): array
    {
        $text = Str::lower($blog->slug.' '.$blog->title.' '.$blog->meta_keywords);

        $words = preg_split('/[^a-z0-9]+/', str_replace('-', ' ', $text), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_filter(
            $words,
            fn ($word) => strlen($word) > 3 && ! in_array($word, self::STOP_WORDS, true)
        )));
    }

    /**
     * Store the uploaded featured image (converted to webp) on the given post.
     *
     * Mirrors the catalog category image flow: the admin `media.images`
     * component submits files as `image[<id>] => file`. An absent `image` key
     * means the admin removed the image, so it is deleted.
     */
    public function uploadImage(array $data, $blog): void
    {
        if (! empty($data['image']) && is_array($data['image'])) {
            foreach ($data['image'] as $imageId => $image) {
                $file = 'image.'.$imageId;

                if (request()->hasFile($file)) {
                    if ($blog->image) {
                        Storage::delete($blog->image);
                    }

                    $encoded = image_manager()->read(request()->file($file))->encodeByExtension('webp', quality: webp_quality());

                    $blog->image = 'blog/'.$blog->id.'/'.Str::random(40).'.webp';

                    Storage::put($blog->image, (string) $encoded);

                    $blog->save();
                }
            }

            return;
        }

        if ($blog->image) {
            Storage::delete($blog->image);

            $blog->image = null;

            $blog->save();
        }
    }
}

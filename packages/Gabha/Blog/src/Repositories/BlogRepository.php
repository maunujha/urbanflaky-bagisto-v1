<?php

namespace Gabha\Blog\Repositories;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;

class BlogRepository extends Repository
{
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

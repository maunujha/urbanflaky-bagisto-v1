<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

if (! function_exists('image_manager')) {
    /**
     * Get the image manager instance.
     */
    function image_manager(): ImageManager
    {
        return app('image_manager');
    }
}

if (! function_exists('webp_quality')) {
    /**
     * Quality used for every JPG/PNG -> WebP conversion (see config/webp.php).
     */
    function webp_quality(): int
    {
        return (int) config('webp.quality', 85);
    }
}

if (! function_exists('webp_sibling_paths')) {
    /**
     * Candidate original (fallback) paths stored next to a .webp file —
     * same basename, each configured fallback extension. Used both for
     * lookup and for cleaning up siblings when the .webp is deleted.
     */
    function webp_sibling_paths(string $path): array
    {
        $base = preg_replace('/\.[^.\/]+$/', '', $path);

        return array_map(
            fn (string $extension) => $base.'.'.$extension,
            config('webp.fallback_extensions', ['jpg', 'jpeg', 'png'])
        );
    }
}

if (! function_exists('webp_fallback_path')) {
    /**
     * Existing original sibling for a .webp path (storage-relative), or null.
     * Memoized per request: product listings resolve many images repeatedly.
     */
    function webp_fallback_path(string $path): ?string
    {
        static $memo = [];

        if (array_key_exists($path, $memo)) {
            return $memo[$path];
        }

        foreach (webp_sibling_paths($path) as $candidate) {
            if (Storage::exists($candidate)) {
                return $memo[$path] = $candidate;
            }
        }

        return $memo[$path] = null;
    }
}

if (! function_exists('webp_store_fallback')) {
    /**
     * Keep the uploaded original next to its generated .webp (same basename)
     * so storefront <picture> tags can offer a non-WebP fallback <img>.
     */
    function webp_store_fallback(UploadedFile $file, string $webpPath): void
    {
        if (! config('webp.keep_original', true)) {
            return;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: (string) $file->guessExtension());

        if (! in_array($extension, config('webp.fallback_extensions', ['jpg', 'jpeg', 'png']))) {
            return;
        }

        Storage::putFileAs(
            dirname($webpPath),
            $file,
            pathinfo($webpPath, PATHINFO_FILENAME).'.'.$extension
        );
    }
}

if (! function_exists('webp_picture_html')) {
    /**
     * Wrap <img> tags pointing at /storage JPG/PNG files in <picture> with a
     * WebP <source> when a same-basename .webp sibling exists on disk.
     * Used for RTE (TinyMCE) content: CMS pages and blog posts.
     */
    function webp_picture_html(?string $html): string
    {
        if (! $html || stripos($html, '<img') === false) {
            return (string) $html;
        }

        $result = preg_replace_callback(
            '/<img\b[^>]*\bsrc=["\']([^"\']+\.(?:jpe?g|png))["\'][^>]*>/i',
            function (array $match) {
                $url = $match[1];

                $position = strpos($url, '/storage/');

                if ($position === false) {
                    return $match[0];
                }

                $relative = preg_replace(
                    '/\.[^.\/]+$/',
                    '.webp',
                    substr($url, $position + strlen('/storage/'))
                );

                if (! Storage::exists($relative)) {
                    return $match[0];
                }

                $webpUrl = substr($url, 0, $position).'/storage/'.$relative;

                return '<picture><source type="image/webp" srcset="'.e($webpUrl).'">'.$match[0].'</picture>';
            },
            $html
        );

        return $result ?? $html;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateWebpImages extends Command
{
    protected $signature = 'webp:convert
        {--fallbacks : Also generate JPEG fallbacks for WebP-only images (pre-existing uploads have no original)}
        {--dir=* : Limit to specific top-level storage directories (default: all known image directories)}';

    protected $description = 'Convert existing JPG/PNG images to WebP siblings, keeping originals as <picture> fallbacks';

    /**
     * Top-level directories under storage/app/public that hold catalog/CMS images.
     */
    protected const DEFAULT_DIRS = [
        'product',
        'category',
        'theme',
        'tinymce',
        'blog',
        'lookbook',
        'attribute_option',
    ];

    public function handle(): int
    {
        $directories = $this->option('dir') ?: self::DEFAULT_DIRS;

        $converted = $fallbacks = $skipped = $failed = 0;

        foreach ($directories as $directory) {
            if (! Storage::directoryExists($directory)) {
                continue;
            }

            foreach (Storage::allFiles($directory) as $path) {
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    /* Original without a WebP sibling -> create the .webp. */
                    $webpPath = preg_replace('/\.[^.\/]+$/', '.webp', $path);

                    if (Storage::exists($webpPath)) {
                        $skipped++;

                        continue;
                    }

                    try {
                        $encoded = image_manager()
                            ->read(Storage::path($path))
                            ->encodeByExtension('webp', quality: webp_quality());

                        Storage::put($webpPath, (string) $encoded);

                        $converted++;

                        $this->line("WebP:     {$path} -> {$webpPath}");
                    } catch (Throwable $e) {
                        $failed++;

                        $this->warn("Failed:   {$path} ({$e->getMessage()})");
                    }
                } elseif ($extension === 'webp' && $this->option('fallbacks')) {
                    /*
                     * WebP-only image (uploaded before originals were kept) ->
                     * generate a JPEG so <picture> has a non-WebP fallback.
                     */
                    if (webp_fallback_path($path)) {
                        $skipped++;

                        continue;
                    }

                    $jpgPath = preg_replace('/\.webp$/', '.jpg', $path);

                    try {
                        $image = image_manager()->read(Storage::path($path));

                        /* Flatten any alpha channel onto white before JPEG encode. */
                        if (method_exists($image, 'blendTransparency')) {
                            $image = $image->blendTransparency('ffffff');
                        }

                        Storage::put($jpgPath, (string) $image->toJpeg(quality: webp_quality()));

                        $fallbacks++;

                        $this->line("Fallback: {$path} -> {$jpgPath}");
                    } catch (Throwable $e) {
                        $failed++;

                        $this->warn("Failed:   {$path} ({$e->getMessage()})");
                    }
                }
            }
        }

        $this->newLine();

        $this->info("WebP created: {$converted} · JPEG fallbacks: {$fallbacks} · skipped (already done): {$skipped} · failed: {$failed}");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}

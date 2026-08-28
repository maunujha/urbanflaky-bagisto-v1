<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Gabha\Blog\Models\Blog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Pre-generate the resized blog derivatives that the storefront requests.
 *
 * Webkul\ImageCache resizes on the fly and, despite the name, caches nothing
 * server-side — it re-decodes and re-encodes the source on every cold request.
 * For blog imagery that source can be 18 megapixels, so a cold request costs
 * real CPU.
 *
 * nginx is configured as `location ^~ /cache/ { try_files $uri /index.php...; }`,
 * so a file that actually exists at public/cache/<template>/<path> is served
 * statically and never reaches PHP. This command writes those files. It is
 * purely an optimisation: delete public/cache and the resizer still answers.
 *
 * Run after a deploy, or after publishing a post with a new image.
 */
class WarmBlogImages extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'blog:warm-images
                            {--force : Regenerate derivatives that already exist}';

    /**
     * The console command description.
     */
    protected $description = 'Pre-generate resized blog images so nginx can serve them without booting PHP';

    /**
     * Templates to generate, keyed by the URL segment used in /cache/<template>/.
     */
    protected const TEMPLATES = [
        'blog_card' => \App\Support\ImageTemplates\BlogCard::class,
        'blog_wide' => \App\Support\ImageTemplates\BlogWide::class,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $images = Blog::query()
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->pluck('image')
            ->unique()
            ->values();

        if ($images->isEmpty()) {
            $this->info('No blog images to warm.');

            return self::SUCCESS;
        }

        $written = $skipped = $failed = 0;
        $sourceBytes = $outputBytes = 0;

        foreach ($images as $relative) {
            $source = Storage::disk('public')->path($relative);

            if (! is_file($source)) {
                $this->warn("  missing source: {$relative}");
                $failed++;

                continue;
            }

            $sourceBytes += filesize($source);

            foreach (self::TEMPLATES as $template => $filterClass) {
                $target = public_path('cache/'.$template.'/'.$relative);

                if (is_file($target) && ! $this->option('force')) {
                    $outputBytes += filesize($target);
                    $skipped++;

                    continue;
                }

                try {
                    $directory = dirname($target);

                    if (! is_dir($directory)) {
                        mkdir($directory, 0o755, true);
                    }

                    $image = image_manager()->read($source);
                    $image = (new $filterClass)->applyFilter($image);

                    file_put_contents($target, (string) $image->encodeByMediaType());

                    $outputBytes += filesize($target);
                    $written++;
                } catch (Throwable $e) {
                    $this->error("  {$template}/{$relative}: ".$e->getMessage());
                    $failed++;
                }
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%d written, %d already present, %d failed.',
            $written,
            $skipped,
            $failed
        ));
        $this->line(sprintf(
            'Sources %s → derivatives %s across %d posts.',
            $this->humanBytes($sourceBytes),
            $this->humanBytes($outputBytes),
            $images->count()
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Format a byte count for the summary line.
     */
    protected function humanBytes(int $bytes): string
    {
        return $bytes >= 1048576
            ? number_format($bytes / 1048576, 1).' MB'
            : number_format($bytes / 1024, 1).' KB';
    }
}

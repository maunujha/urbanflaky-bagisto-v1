<?php

declare(strict_types=1);

namespace App\Support\ImageTemplates;

use Intervention\Image\Interfaces\ImageInterface;

/**
 * Blog hero / social-share image template.
 *
 * Used for the article's featured image and its og:image. Scales only — the
 * article renders the image at its natural aspect ratio (`h-auto`), so cropping
 * here would change the page.
 *
 * 1600px covers the article column (max-w-3xl, 768px) at 2x DPR and stays
 * comfortably above the 1200x630 that link previews want.
 */
class BlogWide
{
    /**
     * Longest edge, in pixels, for a hero-sized blog image.
     */
    protected int $width = 1600;

    /**
     * Apply the filter to the image.
     *
     * scaleDown never enlarges, so a small upload is passed through untouched
     * rather than being upscaled into a bigger file.
     */
    public function applyFilter(ImageInterface $image): ImageInterface
    {
        return $image->scaleDown(width: $this->width);
    }

    /**
     * Get the configured width.
     */
    public function getWidth(): int
    {
        return $this->width;
    }
}

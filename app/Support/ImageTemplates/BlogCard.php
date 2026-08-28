<?php

declare(strict_types=1);

namespace App\Support\ImageTemplates;

use Intervention\Image\Interfaces\ImageInterface;

/**
 * Blog card image template.
 *
 * The core small/medium/large templates all `cover()` to a square, which would
 * re-crop editorial imagery. Blog cards are 16:10 and already crop in CSS via
 * `object-cover`, so this template only scales the image down and leaves the
 * composition alone.
 *
 * 800px covers the widest card at 2x DPR: the listing renders ~380px per card
 * on a three-column desktop grid, the home grid ~290px on four columns.
 */
class BlogCard
{
    /**
     * Longest edge, in pixels, for a card-sized blog image.
     */
    protected int $width = 800;

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

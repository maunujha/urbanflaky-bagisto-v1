<?php

declare(strict_types=1);

namespace App\Support\ImageTemplates;

use Intervention\Image\Interfaces\ImageInterface;

/**
 * Category card image template.
 *
 * The homepage category cards are full-bleed `object-fit: cover` tiles (1:1 on
 * mobile, 4:5 from 525px), so they used to download the untouched original. The
 * core `large` template is no good here — for category paths it covers to a
 * 165px square. This template only scales down, so that the SHORT edge is at
 * most 800px: enough for the widest card at 2x DPR in either orientation,
 * while CSS keeps doing the cropping.
 */
class CategoryCard
{
    /**
     * Target length, in pixels, of the image's shorter edge.
     */
    protected int $shortEdge = 800;

    /**
     * Apply the filter to the image.
     *
     * Never enlarges: a source whose short edge is already within the target is
     * passed through untouched.
     */
    public function applyFilter(ImageInterface $image): ImageInterface
    {
        $short = min($image->width(), $image->height());

        if ($short <= $this->shortEdge) {
            return $image;
        }

        return $image->scale(width: (int) round($image->width() * $this->shortEdge / $short));
    }
}

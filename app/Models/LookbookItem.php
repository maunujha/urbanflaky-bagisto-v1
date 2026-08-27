<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LookbookItem extends Model
{
    protected $fillable = [
        'title',
        'type',
        'image',
        'video',
        'video_url',
        'permalink',
        'collection_name',
        'caption',
        'product_ids',
        'display_order',
        'is_featured',
        'status',
    ];

    protected $casts = [
        'product_ids'  => 'array',
        'is_featured'  => 'boolean',
        'status'       => 'boolean',
        'display_order'=> 'integer',
    ];

    /**
     * Full public URL to the stored thumbnail/campaign image. Prefers the
     * generated WebP sibling (uploads keep both) so the storefront serves the
     * ~3x smaller file instead of the original JPG/PNG.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->image)) {
            return null;
        }

        return Storage::url($this->preferWebp($this->image));
    }

    /**
     * Return the same-basename .webp sibling when it exists on disk, otherwise
     * the stored path unchanged.
     */
    protected function preferWebp(string $path): string
    {
        if (preg_match('/\.webp$/i', $path)) {
            return $path;
        }

        $webp = preg_replace('/\.[^.\/]+$/', '.webp', $path);

        return Storage::exists($webp) ? $webp : $path;
    }

    /**
     * Effective playable video source — an uploaded file takes precedence
     * over an external URL.
     */
    public function getVideoSrcAttribute(): ?string
    {
        if (! empty($this->video)) {
            return Storage::url($this->video);
        }

        return $this->video_url ?: null;
    }

    /**
     * Whether this item is a reel/video.
     */
    public function getIsReelAttribute(): bool
    {
        return $this->type === 'reel';
    }
}

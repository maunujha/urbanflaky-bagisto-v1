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
     * Full public URL to the stored thumbnail/campaign image.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->image)) {
            return null;
        }

        return Storage::url($this->image);
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

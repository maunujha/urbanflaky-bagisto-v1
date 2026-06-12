<?php

namespace Gabha\Blog\Models;

use Gabha\Blog\ViewComposers\HomeBlogComposer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Blog extends Model
{
    /**
     * Table associated with the model.
     *
     * @var string
     */
    protected $table = 'blogs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'slug',
        'author',
        'short_description',
        'content',
        'image',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'status',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'status'       => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Keep the cached home-page grid in sync with every create / update / delete.
     */
    protected static function booted(): void
    {
        $forget = fn () => Cache::forget(HomeBlogComposer::CACHE_KEY);

        static::saved($forget);
        static::deleted($forget);
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['image_url'];

    /**
     * Public URL of the featured image (null when none uploaded).
     */
    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return Storage::url($this->image);
    }

    /**
     * Effective SEO title — falls back to the post title.
     */
    public function getSeoTitleAttribute(): string
    {
        return $this->meta_title ?: $this->title;
    }

    /**
     * Effective SEO description — falls back to the short description, then a
     * plain-text snippet of the content. Trimmed to a meta-friendly length.
     */
    public function getSeoDescriptionAttribute(): string
    {
        $description = $this->meta_description
            ?: ($this->short_description ?: strip_tags((string) $this->content));

        return Str::limit(trim(preg_replace('/\s+/', ' ', $description)), 160, '');
    }

    /**
     * Canonical storefront URL for this post.
     */
    public function getUrlAttribute(): string
    {
        return url('blog/'.$this->slug);
    }

    /**
     * Scope: only published posts whose publish date has arrived.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 1)
            ->where(function (Builder $builder) {
                $builder->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }
}

<?php

namespace Gabha\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * An admin-edited subject for one email type (EmailCatalog key).
 */
class EmailTemplateSetting extends Model
{
    protected $guarded = ['id'];

    protected const CACHE_KEY = 'notifications.subjects';

    /**
     * @return array<string, string> key => custom subject
     */
    public static function subjects(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::whereNotNull('subject')
            ->where('subject', '!=', '')
            ->pluck('subject', 'key')
            ->all());
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }
}

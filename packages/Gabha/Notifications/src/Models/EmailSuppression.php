<?php

namespace Gabha\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An address that opted out of one kind of non-essential email. Order,
 * shipping and account emails are never suppressed.
 */
class EmailSuppression extends Model
{
    public const SCOPE_REVIEWS = 'reviews';

    public const SCOPE_MARKETING = 'marketing';

    public const SCOPES = [self::SCOPE_REVIEWS, self::SCOPE_MARKETING];

    protected $guarded = ['id'];

    public static function isSuppressed(string $email, string $scope): bool
    {
        return static::where('email', mb_strtolower(trim($email)))->where('scope', $scope)->exists();
    }

    public static function suppress(string $email, string $scope, string $reason = 'unsubscribed'): void
    {
        static::firstOrCreate(
            ['email' => mb_strtolower(trim($email)), 'scope' => $scope],
            ['reason' => $reason],
        );
    }
}

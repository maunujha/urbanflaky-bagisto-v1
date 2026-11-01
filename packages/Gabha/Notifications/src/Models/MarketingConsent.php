<?php

namespace Gabha\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only consent ledger. Never updated or deleted: each change of a
 * newsletter subscription adds a row, so the history survives unsubscribes
 * (Bagisto deletes the subscriber row on its own unsubscribe link).
 */
class MarketingConsent extends Model
{
    public const GRANTED = 'granted';

    public const WITHDRAWN = 'withdrawn';

    public const CONFIRMATION_SENT = 'confirmation_sent';

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    public static function record(string $email, string $action, ?int $customerId = null, ?string $source = null): void
    {
        $request = app()->runningInConsole() ? null : request();

        static::create([
            'email'       => mb_strtolower(trim($email)),
            'customer_id' => $customerId,
            'action'      => $action,
            'source'      => mb_substr($source ?? $request?->route()?->getName() ?? ($request ? $request->path() : 'console'), 0, 96),
            'ip'          => $request?->ip(),
            'user_agent'  => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
        ]);
    }

    public static function latestFor(string $email): ?self
    {
        return static::where('email', mb_strtolower(trim($email)))->latest('id')->first();
    }
}

<?php

namespace Gabha\Notifications\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * One row per outgoing email: queued by EmailDispatcher, or recorded on send
 * for Bagisto's own mailables. Status moves queued → sent | failed; an email
 * withheld because the address opted out is recorded as suppressed.
 */
class EmailLog extends Model
{
    use MassPrunable;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SUPPRESSED = 'suppressed';

    public const STATUSES = [self::STATUS_QUEUED, self::STATUS_SENT, self::STATUS_FAILED, self::STATUS_SUPPRESSED];

    /** Emails that went, or are going, out: what caps and gaps count. */
    public const OUTGOING = [self::STATUS_QUEUED, self::STATUS_SENT];

    /**
     * A failed row's dedupe key is parked as "{key}#failed{id}": a later send
     * may take the key, and a retry can still tell which key it held.
     */
    public const PARKED_KEY = '#failed';

    public const CATEGORY_TRANSACTIONAL = 'transactional';

    public const CATEGORY_MARKETING = 'marketing';

    /** Follow-ups about the customer's own order (review and feedback requests). */
    public const CATEGORY_LIFECYCLE = 'lifecycle';

    /** Admin campaigns (new collections, promotions): own daily cap. */
    public const CATEGORY_CAMPAIGN = 'campaign';

    protected $guarded = ['id'];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function originalDedupeKey(): ?string
    {
        return $this->dedupe_key ? Str::before($this->dedupe_key, self::PARKED_KEY) : null;
    }

    /** Admin may re-queue it: it failed in the queue and its job is still kept. */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED && filled($this->job_uuid);
    }

    public function prunable()
    {
        return static::where('created_at', '<', now()->subDays((int) config('notifications.log_retention_days', 180)));
    }
}

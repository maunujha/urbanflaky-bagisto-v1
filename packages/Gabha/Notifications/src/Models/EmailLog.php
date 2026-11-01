<?php

namespace Gabha\Notifications\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One row per outgoing email: queued by EmailDispatcher, or recorded on send
 * for Bagisto's own mailables. Status moves queued → sent | failed.
 */
class EmailLog extends Model
{
    use MassPrunable;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const CATEGORY_TRANSACTIONAL = 'transactional';

    public const CATEGORY_MARKETING = 'marketing';

    /** Follow-ups about the customer's own order (review requests). */
    public const CATEGORY_LIFECYCLE = 'lifecycle';

    protected $guarded = ['id'];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function prunable()
    {
        return static::where('created_at', '<', now()->subDays((int) config('notifications.log_retention_days', 180)));
    }
}

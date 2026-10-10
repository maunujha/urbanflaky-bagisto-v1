<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One courier scan or status change. Unique per order on `fingerprint`, so a
 * webhook replayed by Shiprocket or re-read by the poller is stored once.
 */
class ShiprocketTrackingEvent extends Model
{
    protected $fillable = [
        'order_id',
        'stage',
        'status',
        'activity',
        'location',
        'event_at',
        'source',
        'fingerprint',
    ];

    protected $casts = [
        'event_at' => 'datetime',
    ];
}

<?php

namespace Gabha\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Sales\Models\Order;

/**
 * A customer's 1–5 rating of their order experience, from the feedback email.
 */
class OrderFeedback extends Model
{
    protected $table = 'order_feedback';

    protected $guarded = ['id'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

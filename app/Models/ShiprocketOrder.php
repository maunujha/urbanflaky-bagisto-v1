<?php

namespace App\Models;

use App\Services\Shiprocket\ShipmentStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Sales\Models\Order;

/**
 * One Bagisto order's Shiprocket fulfilment record. `status` is a
 * ShipmentStage code; `current_status` is the courier's own wording.
 */
class ShiprocketOrder extends Model
{
    protected $fillable = [
        'order_id',
        'shiprocket_order_id',
        'shipment_id',
        'awb_code',
        'courier_name',
        'courier_company_id',
        'status',
        'current_status',
        'is_cod',
        'push_attempts',
        'last_error',
        'last_error_at',
        'pushed_at',
        'awb_assigned_at',
        'pickup_scheduled_at',
        'shipped_at',
        'delivered_at',
        'last_event_at',
        'last_synced_at',
        'etd',
        'label_url',
        'manifest_url',
        'invoice_url',
    ];

    protected $casts = [
        'is_cod'              => 'boolean',
        'push_attempts'       => 'integer',
        'last_error_at'       => 'datetime',
        'pushed_at'           => 'datetime',
        'awb_assigned_at'     => 'datetime',
        'pickup_scheduled_at' => 'datetime',
        'shipped_at'          => 'datetime',
        'delivered_at'        => 'datetime',
        'last_event_at'       => 'datetime',
        'last_synced_at'      => 'datetime',
        'etd'                 => 'date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShiprocketTrackingEvent::class, 'order_id', 'order_id')
            ->orderByDesc('event_at')
            ->orderByDesc('id');
    }

    public function isPushed(): bool
    {
        return filled($this->shiprocket_order_id);
    }

    public function hasAwb(): bool
    {
        return filled($this->awb_code);
    }

    /** Courier can still be changed and the order cancelled in Shiprocket. */
    public function isBeforePickup(): bool
    {
        return ! in_array($this->status, ShipmentStage::IN_CARRIER_HANDS, true)
            && $this->status !== ShipmentStage::CANCELED;
    }

    public function stageLabel(): string
    {
        return ShipmentStage::label($this->status);
    }

    /** Our own tracking page, never the courier's. */
    public function getTrackingUrlAttribute(): ?string
    {
        return $this->hasAwb() ? route('shop.track-order.index', ['awb' => $this->awb_code]) : null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiprocketOrder extends Model
{
    protected $fillable = [
        'order_id',
        'shiprocket_order_id',
        'shipment_id',
        'awb_code',
        'courier_name',
        'status',
    ];

    public function order()
    {
        return $this->belongsTo(\Webkul\Sales\Models\Order::class, 'order_id');
    }

    public function getTrackingUrlAttribute(): ?string
    {
        if (! $this->awb_code) return null;
        return 'https://shiprocket.co/tracking/' . $this->awb_code;
    }
}

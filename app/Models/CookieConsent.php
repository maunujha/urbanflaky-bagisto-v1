<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Customer\Models\Customer;

class CookieConsent extends Model
{
    /**
     * Mass assignable attributes.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'analytics',
        'marketing',
        'preferences',
        'consent_version',
    ];

    /**
     * Attribute casting.
     *
     * @var array
     */
    protected $casts = [
        'analytics'   => 'boolean',
        'marketing'   => 'boolean',
        'preferences' => 'boolean',
    ];

    /**
     * The customer this consent belongs to.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }
}

<?php

namespace Webkul\FAQ\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaqCategory extends Model
{
    /**
     * Table associated with the model.
     *
     * @var string
     */
    protected $table = 'faq_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'sort_order',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'status'     => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the FAQs for the category.
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class, 'faq_category_id');
    }

    /**
     * Get only the active FAQs for the category, ordered by sort order.
     */
    public function activeFaqs(): HasMany
    {
        return $this->faqs()
            ->where('status', 1)
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}

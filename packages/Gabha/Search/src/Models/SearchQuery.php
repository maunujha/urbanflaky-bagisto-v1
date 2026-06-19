<?php

declare(strict_types=1);

namespace Gabha\Search\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single recorded storefront search (term + parsed intent + outcome).
 *
 * @property string      $term
 * @property string|null $clean_query
 * @property string|null $color
 * @property float|null  $price_min
 * @property float|null  $price_max
 * @property string|null $gender
 * @property string|null $product_type
 * @property string|null $category_slug
 * @property bool        $had_intent
 * @property string|null $filters
 * @property int         $results_count
 * @property string|null $relaxed_to
 * @property string|null $channel
 * @property string|null $locale
 * @property int|null    $customer_id
 */
class SearchQuery extends Model
{
    protected $table = 'search_queries';

    protected $fillable = [
        'term',
        'clean_query',
        'color',
        'price_min',
        'price_max',
        'gender',
        'product_type',
        'category_slug',
        'had_intent',
        'filters',
        'results_count',
        'relaxed_to',
        'channel',
        'locale',
        'customer_id',
    ];

    protected $casts = [
        'price_min'     => 'float',
        'price_max'     => 'float',
        'had_intent'    => 'boolean',
        'results_count' => 'integer',
        'customer_id'   => 'integer',
    ];

    /**
     * Searches that returned nothing — the priority list for merchandising.
     */
    public function scopeZeroResult($query)
    {
        return $query->where('results_count', 0);
    }
}

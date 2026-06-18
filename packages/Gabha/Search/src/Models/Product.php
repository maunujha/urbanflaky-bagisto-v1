<?php

declare(strict_types=1);

namespace Gabha\Search\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Searchable;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Product\Models\Product as BaseProduct;

/**
 * Searchable view of a Bagisto product.
 *
 * Extends the core product model and layers Scout's Searchable trait on top.
 * Because the trait lives ONLY on this subclass (never on the core model that
 * Bagisto saves through), Scout's automatic model-event observers never fire
 * during normal catalog operations — all (re)indexing is driven explicitly by
 * the package's Bagisto-event listener and jobs, after the product_flat index
 * has been refreshed. This is the same consistency guarantee Bagisto's own
 * Elasticsearch indexer relies on.
 *
 * `toSearchableArray()` assembles the flat document Meilisearch stores. The
 * EAV-sourced fields (brand, color) are resolved to their option labels and
 * the locale/channel-scoped fields are read from product_flat for the store's
 * default channel + locale (only `en` is active in this store).
 */
class Product extends BaseProduct
{
    use Searchable;

    /**
     * Process-lifetime caches so a full reindex resolves attribute metadata and
     * option labels once, not once per product.
     *
     * @var array<string, \Webkul\Attribute\Contracts\Attribute|null>
     */
    protected static array $attributeCache = [];

    /**
     * @var array<int, array<int, string>>  attribute_id => [option_id => admin_name]
     */
    protected static array $optionCache = [];

    /**
     * Per-instance memo of the product_flat row.
     */
    protected ?object $flatRowCache = null;

    /**
     * The Meilisearch index this model is stored in.
     */
    public function searchableAs(): string
    {
        return (string) config('gabha-search.index', 'products');
    }

    /**
     * Only individually-visible, enabled products belong in the search index.
     * Variants (visible_individually = 0) are excluded — their parent carries
     * them. Disabled products are removed by the sync job via unsearchable().
     */
    public function shouldBeSearchable(): bool
    {
        if (! config('gabha-search.enabled', true)) {
            return false;
        }

        $flat = $this->flatRow();

        return $flat
            && (int) $flat->status === 1
            && (int) $flat->visible_individually === 1;
    }

    /**
     * Eager-load the relations the document needs so a bulk reindex doesn't N+1.
     */
    public function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(['categories', 'attribute_values', 'price_indices']);
    }

    /**
     * The document pushed to Meilisearch. Keys map 1:1 to the fields configured
     * in config/gabha-search.php (searchable / filterable / sortable).
     */
    public function toSearchableArray(): array
    {
        $flat = $this->flatRow();

        return [
            'id'                => (int) $this->id,
            'sku'               => (string) $this->sku,
            'name'              => $flat->name ?? (string) $this->sku,
            'slug'              => $flat->url_key ?? null,
            'short_description' => $this->toPlainText($flat->short_description ?? null),
            'description'       => $this->toPlainText($flat->description ?? null),
            'category_names'    => $this->categoryNames(),
            'category_ids'      => $this->categoryIds(),
            'brand'             => $this->optionLabel('brand'),
            'color'             => $this->optionLabel('color'),
            'tags'              => $this->resolveTags(),
            'price'             => $this->minPrice($flat),
            'created_at'        => optional($this->created_at)->getTimestamp(),
        ];
    }

    /**
     * The product_flat row for the default channel + locale (memoized).
     */
    protected function flatRow(): ?object
    {
        if ($this->flatRowCache !== null) {
            return $this->flatRowCache;
        }

        $channel = core()->getDefaultChannelCode();
        $locale = core()->getDefaultChannel()->default_locale->code ?? config('app.locale');

        return $this->flatRowCache = DB::table('product_flat')
            ->where('product_id', $this->id)
            ->where('channel', $channel)
            ->where('locale', $locale)
            ->first();
    }

    /**
     * Translated names of the product's categories (root category excluded).
     *
     * @return array<int, string>
     */
    protected function categoryNames(): array
    {
        return $this->loadMissing('categories')->categories
            ->filter(fn ($category) => $category->parent_id !== null)
            ->map(fn ($category) => $category->name)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    protected function categoryIds(): array
    {
        return $this->loadMissing('categories')->categories
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Resolve a select-attribute's chosen option to its admin label.
     */
    protected function optionLabel(string $code): ?string
    {
        $attribute = $this->attribute($code);

        if (! $attribute) {
            return null;
        }

        $value = $this->loadMissing('attribute_values')->attribute_values
            ->firstWhere('attribute_id', $attribute->id);

        $optionId = $value?->integer_value;

        if (! $optionId) {
            return null;
        }

        if (! isset(static::$optionCache[$attribute->id])) {
            static::$optionCache[$attribute->id] = AttributeOption::query()
                ->where('attribute_id', $attribute->id)
                ->pluck('admin_name', 'id')
                ->all();
        }

        return static::$optionCache[$attribute->id][$optionId] ?? null;
    }

    /**
     * Cached attribute lookup by code.
     */
    protected function attribute(string $code): mixed
    {
        if (! array_key_exists($code, static::$attributeCache)) {
            static::$attributeCache[$code] = app(AttributeRepository::class)
                ->findOneByField('code', $code);
        }

        return static::$attributeCache[$code];
    }

    /**
     * Lowest indexed price across customer groups (falls back to flat price).
     */
    protected function minPrice(?object $flat): float
    {
        $min = $this->loadMissing('price_indices')->price_indices->min('min_price');

        return (float) ($min ?? $flat->price ?? 0);
    }

    /**
     * Resolve the `tags` field from the configured source. This store has no
     * native tags attribute, so the default ('tags_source' => null) yields an
     * empty array. Switch the source in config without touching this code.
     *
     * @return array<int, string>
     */
    protected function resolveTags(): array
    {
        $source = config('gabha-search.tags_source');

        if (empty($source)) {
            return [];
        }

        if ($source === 'category_brand') {
            return collect($this->categoryNames())
                ->merge([$this->optionLabel('brand'), $this->optionLabel('color')])
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if (str_starts_with($source, 'attribute:')) {
            $value = $this->optionLabel(substr($source, strlen('attribute:')));

            return $value ? [$value] : [];
        }

        return [];
    }

    /**
     * Strip HTML/markup so descriptions index as clean searchable text.
     */
    protected function toPlainText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text)) ?: null;
    }
}

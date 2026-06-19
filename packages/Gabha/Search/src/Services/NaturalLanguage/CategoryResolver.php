<?php

declare(strict_types=1);

namespace Gabha\Search\Services\NaturalLanguage;

use Illuminate\Support\Facades\DB;

/**
 * Resolves a category slug (a gender section like "mens", or a named section
 * like "bottom-wear") to the ids of that category AND all of its descendants,
 * using Bagisto's nested-set bounds (`_lft` / `_rgt`).
 *
 * Returning the whole subtree matters because products are assigned to leaf
 * categories ("mens-tshirts", "bottom-wear") that may not also carry the parent
 * ("mens"). Filtering on the subtree means "for men" matches every men's product
 * regardless of which sub-section it lives in.
 *
 * Results are memoised for the request — categories change rarely and each lookup
 * is a single indexed query, only run when a query actually carries gender/section
 * intent.
 */
class CategoryResolver
{
    /**
     * @var array<string, array<int, int>>  slug => descendant category ids
     */
    protected array $cache = [];

    /**
     * Category ids for a gender intent ('men' | 'women'), resolved through the
     * configured slug. Empty when the gender or its category does not exist.
     *
     * @return array<int, int>
     */
    public function genderIds(string $gender): array
    {
        $slug = config("gabha-search.natural_language.gender.{$gender}.slug");

        return $slug ? $this->descendantIds((string) $slug) : [];
    }

    /**
     * Category ids for a slug and its entire subtree.
     *
     * @return array<int, int>
     */
    public function descendantIds(string $slug): array
    {
        if (array_key_exists($slug, $this->cache)) {
            return $this->cache[$slug];
        }

        return $this->cache[$slug] = $this->resolve($slug);
    }

    /**
     * @return array<int, int>
     */
    protected function resolve(string $slug): array
    {
        $root = DB::table('categories as c')
            ->join('category_translations as ct', 'ct.category_id', '=', 'c.id')
            ->where('ct.slug', $slug)
            ->select('c._lft', 'c._rgt')
            ->first();

        if (! $root) {
            return [];
        }

        return DB::table('categories')
            ->where('_lft', '>=', $root->_lft)
            ->where('_rgt', '<=', $root->_rgt)
            ->orderBy('_lft')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}

<?php

namespace Webkul\Shop\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Shop\Http\Resources\ProductResource;

/**
 * Sale collection page (/sale).
 *
 * Lists every individually-visible product that currently carries an active
 * special-price markdown (`special_price < price` and within the optional
 * from/to window), biggest discount first. The page shell + SEO/JSON-LD render
 * server-side; the product grid hydrates through the paginated `products()` JSON
 * endpoint so it reuses the same Vue product card the rest of the storefront does.
 */
class SaleController extends Controller
{
    /**
     * Products per page in the grid (and the Load-More batch size).
     */
    private const PER_PAGE = 12;

    /**
     * Cap on products embedded in the CollectionPage ItemList JSON-LD.
     */
    private const SCHEMA_LIMIT = 24;

    public function __construct(protected ProductRepository $productRepository) {}

    /**
     * Render the sale landing page (hero + SEO + structured data). The grid
     * itself is loaded client-side via {@see self::products()}.
     */
    public function index(): View
    {
        $rows = $this->onSaleRows();

        // True total (onSaleRows is capped to the JSON-LD limit, so count it directly).
        $count = $this->onSaleBaseQuery()->count();

        $maxDiscount = (int) $rows->max('discount_percent');

        return view('shop::sale.index', [
            'count'          => $count,
            'maxDiscount'    => $maxDiscount,
            'structuredData' => $this->structuredData($rows, $count),
        ]);
    }

    /**
     * Paginated on-sale products as the storefront's ProductResource, in
     * biggest-discount-first order — consumed by the `v-sale` grid component.
     */
    public function products(): JsonResource
    {
        $ids = $this->onSaleIds();

        $currentPage = Paginator::resolveCurrentPage('page');

        $pagedIds = array_slice($ids, ($currentPage - 1) * self::PER_PAGE, self::PER_PAGE);

        $products = empty($pagedIds)
            ? collect()
            : $this->productRepository
                ->with([
                    'attribute_family',
                    'images',
                    'videos',
                    'attribute_values',
                    'price_indices',
                    'inventory_indices',
                    'reviews',
                    'variants',
                    'variants.attribute_family',
                    'variants.attribute_values',
                    'variants.price_indices',
                    'variants.inventory_indices',
                ])
                ->scopeQuery(fn ($query) => $query->whereIn('products.id', $pagedIds))
                ->get();

        // Preserve the biggest-discount-first order the id list defines.
        $ordered = collect($pagedIds)
            ->map(fn ($id) => $products->firstWhere('id', $id))
            ->filter()
            ->values();

        $paginator = new LengthAwarePaginator(
            $ordered,
            count($ids),
            self::PER_PAGE,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return ProductResource::collection($paginator);
    }

    /**
     * Ids of all currently-on-sale products, biggest discount first.
     *
     * @return array<int, int>
     */
    protected function onSaleIds(): array
    {
        return $this->onSaleBaseQuery()
            ->orderByRaw('((pf.price - pf.special_price) / pf.price) DESC')
            ->orderByDesc('pf.product_id')
            ->pluck('pf.product_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * On-sale flat rows (with the first image) used for the hero stats and the
     * JSON-LD ItemList — biggest discount first, capped for the schema payload.
     */
    protected function onSaleRows(): \Illuminate\Support\Collection
    {
        return $this->onSaleBaseQuery()
            ->leftJoin('product_images as pi', function ($join) {
                $join->on('pi.product_id', '=', 'pf.product_id')
                    ->whereRaw('pi.id = (SELECT MIN(id) FROM product_images WHERE product_id = pf.product_id)');
            })
            ->orderByRaw('((pf.price - pf.special_price) / pf.price) DESC')
            ->orderByDesc('pf.product_id')
            ->limit(self::SCHEMA_LIMIT)
            ->get([
                'pf.product_id',
                'pf.name',
                'pf.url_key',
                'pf.price',
                'pf.special_price',
                'pi.path as image_path',
            ])
            ->map(function ($row) {
                $row->discount_percent = $row->price > 0
                    ? (int) round((($row->price - $row->special_price) / $row->price) * 100)
                    : 0;

                return $row;
            });
    }

    /**
     * The shared "is on sale right now" constraint over product_flat for the
     * current channel + locale.
     */
    protected function onSaleBaseQuery(): Builder
    {
        $now = Carbon::now();

        return DB::table('product_flat as pf')
            ->where('pf.channel', core()->getCurrentChannelCode())
            ->where('pf.locale', app()->getLocale())
            ->where('pf.status', 1)
            ->where('pf.visible_individually', 1)
            ->whereNotNull('pf.special_price')
            ->where('pf.special_price', '>', 0)
            ->whereColumn('pf.special_price', '<', 'pf.price')
            ->where(fn ($q) => $q->whereNull('pf.special_price_from')->orWhere('pf.special_price_from', '<=', $now))
            ->where(fn ($q) => $q->whereNull('pf.special_price_to')->orWhere('pf.special_price_to', '>=', $now));
    }

    /**
     * Build the CollectionPage schema.org payload (with an ItemList of the
     * discounted products + their offers) for rich results.
     *
     * @return array<string, mixed>
     */
    protected function structuredData(\Illuminate\Support\Collection $rows, int $count): array
    {
        $currency = core()->getCurrentCurrencyCode();

        $items = $rows->values()->map(function ($row, $index) use ($currency) {
            $url = route('shop.product_or_category.index', $row->url_key);

            return [
                '@type'    => 'ListItem',
                'position' => $index + 1,
                'item'     => array_filter([
                    '@type'  => 'Product',
                    'name'   => $row->name,
                    'url'    => $url,
                    'image'  => $row->image_path ? url(Storage::url($row->image_path)) : null,
                    'offers' => [
                        '@type'         => 'Offer',
                        'url'           => $url,
                        'price'         => (string) round((float) $row->special_price, 2),
                        'priceCurrency' => $currency,
                        'availability'  => 'https://schema.org/InStock',
                    ],
                ]),
            ];
        })->all();

        $saleUrl = route('shop.sale.index');

        return [
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type'       => 'CollectionPage',
                    '@id'         => $saleUrl.'#webpage',
                    'name'        => 'Urbanflaky Sale Collection',
                    'description' => 'Shop the Urbanflaky Sale Collection featuring premium oversized t-shirts, dark streetwear, monochrome essentials, and minimalist fashion at discounted prices.',
                    'url'         => $saleUrl,
                    'isPartOf'    => [
                        '@type' => 'WebSite',
                        'name'  => 'Urbanflaky',
                        'url'   => url('/'),
                    ],
                    'mainEntity'  => [
                        '@type'           => 'ItemList',
                        'numberOfItems'   => $count,
                        'itemListElement' => $items,
                    ],
                ],
                [
                    '@type'           => 'BreadcrumbList',
                    'itemListElement' => [
                        [
                            '@type'    => 'ListItem',
                            'position' => 1,
                            'name'     => 'Home',
                            'item'     => route('shop.home.index'),
                        ],
                        [
                            '@type'    => 'ListItem',
                            'position' => 2,
                            'name'     => 'Sale',
                            'item'     => $saleUrl,
                        ],
                    ],
                ],
            ],
        ];
    }
}

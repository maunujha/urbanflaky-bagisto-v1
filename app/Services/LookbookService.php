<?php

namespace App\Services;

use App\Models\LookbookItem;
use Illuminate\Support\Collection;
use Webkul\Product\Repositories\ProductRepository;

class LookbookService
{
    public function __construct(protected ProductRepository $productRepository) {}

    /**
     * All active looks, ordered, with their tagged products resolved.
     */
    public function getLooks(?int $limit = null): array
    {
        $query = LookbookItem::query()
            ->where('status', true)
            ->orderBy('display_order')
            ->orderByDesc('id');

        if ($limit) {
            $query->limit($limit);
        }

        $looks = $query->get();

        return $this->transformLooks($looks);
    }

    /**
     * Shape looks for the storefront and batch-resolve every tagged product once.
     */
    protected function transformLooks(Collection $looks): array
    {
        $allIds = $looks->flatMap(fn ($look) => $look->product_ids ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $products = empty($allIds)
            ? collect()
            : $this->productRepository->findWhereIn('id', $allIds)->keyBy('id');

        return $looks->map(function (LookbookItem $look) use ($products) {
            $tagged = collect($look->product_ids ?? [])
                ->map(fn ($id) => $products->get($id))
                ->filter()
                // Only products with a storefront presence (url_key in the active channel/locale).
                ->filter(fn ($product) => ! empty($product->url_key))
                ->map(fn ($product) => $this->transformProduct($product))
                ->values()
                ->all();

            return [
                'id'              => $look->id,
                'title'           => $look->title,
                'type'            => $look->type,
                'is_reel'         => $look->is_reel,
                'image_url'       => $look->image_url,
                'video_url'       => $look->video_src,
                'permalink'       => $look->permalink,
                'collection_name' => $look->collection_name,
                'caption'         => $look->caption,
                'is_featured'     => $look->is_featured,
                'products'        => $tagged,
            ];
        })->all();
    }

    /**
     * Minimal product payload for the modal / slider product tiles.
     */
    protected function transformProduct($product): array
    {
        return [
            'id'         => $product->id,
            'name'       => $product->name,
            'url'        => route('shop.product_or_category.index', $product->url_key),
            'image'      => product_image()->getProductBaseImage($product)['medium_image_url'] ?? null,
            'price_html' => $product->getTypeInstance()->getPriceHtml(),
        ];
    }
}

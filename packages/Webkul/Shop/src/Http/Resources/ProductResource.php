<?php

namespace Webkul\Shop\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Product\Helpers\Review;

class ProductResource extends JsonResource
{
    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        $this->reviewHelper = app(Review::class);

        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return array
     */
    public function toArray($request)
    {
        $productTypeInstance = $this->getTypeInstance();

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'url_key' => $this->url_key,
            'base_image' => product_image()->getProductBaseImage($this),
            'images' => product_image()->getGalleryImages($this),
            'is_new' => (bool) $this->new,
            'is_featured' => (bool) $this->featured,
            'on_sale' => (bool) $productTypeInstance->haveDiscount(),
            'is_saleable' => (bool) $productTypeInstance->isSaleable(),
            'is_wishlist' => (bool) auth()->guard()->user()?->wishlist_items
                ->where('channel_id', core()->getCurrentChannel()->id)
                ->where('product_id', $this->id)->count(),
            'min_price' => core()->formatPrice($productTypeInstance->getMinimalPrice()),
            'prices' => $productTypeInstance->getProductPrices(),
            'price_html' => $productTypeInstance->getPriceHtml(),
            'ratings' => [
                'average' => $this->reviewHelper->getAverageRating($this),
                'total' => $this->reviewHelper->getTotalRating($this),
            ],
            'reviews' => [
                'total' => $this->reviewHelper->getTotalReviews($this),
            ],
            'type' => $this->type,
            'super_attributes' => $this->when(
                $this->type === 'configurable',
                function () {
                    $product = $this->resource;

                    // Build a map of option IDs that are actually used by at least one saleable variant —
                    // mirrors what ConfigurableOption::getAttributeOptionsData() does on the PDP.
                    $usedOptions = [];

                    foreach ($product->variants as $variant) {
                        if (! $variant->isSaleable()) {
                            continue;
                        }

                        foreach ($product->super_attributes as $attr) {
                            $val = $variant->{$attr->code};

                            if ($val) {
                                $usedOptions[$attr->id][$val] = true;
                            }
                        }
                    }

                    return $product->super_attributes
                        ->map(function ($attr) use ($usedOptions) {
                            $allowed = array_keys($usedOptions[$attr->id] ?? []);

                            if (empty($allowed)) {
                                return null;
                            }

                            return [
                                'id'          => $attr->id,
                                'code'        => $attr->code,
                                'label'       => $attr->admin_name,
                                'swatch_type' => $attr->swatch_type,
                                'options'     => $attr->options
                                    ->whereIn('id', $allowed)
                                    ->sortBy('sort_order')
                                    ->map(fn ($opt) => [
                                        'id'          => $opt->id,
                                        'label'       => $opt->admin_name,
                                        'swatch_value'=> $opt->swatch_value,
                                    ])
                                    ->values(),
                            ];
                        })
                        ->filter()
                        ->values();
                },
                []
            ),
        ];
    }
}

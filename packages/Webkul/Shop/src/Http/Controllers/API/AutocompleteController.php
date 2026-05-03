<?php

namespace Webkul\Shop\Http\Controllers\API;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AutocompleteController extends APIController
{
    public function search(): JsonResponse
    {
        $query = trim(request()->input('query', ''));
        $minLength = max(2, (int) core()->getConfigData('catalog.products.search.min_query_length'));

        if (mb_strlen($query) < $minLength) {
            return response()->json([]);
        }

        $limit = min(8, max(1, (int) (core()->getConfigData('catalog.products.search.autocomplete_limit') ?: 8)));
        $channel = core()->getCurrentChannel()->code;
        $locale = app()->getLocale();

        $products = DB::table('product_flat as pf')
            ->leftJoin('product_images as pi', function ($join) {
                $join->on('pi.product_id', '=', 'pf.product_id')
                    ->whereRaw('pi.id = (SELECT MIN(id) FROM product_images WHERE product_id = pf.product_id)');
            })
            ->select([
                'pf.product_id as id',
                'pf.name',
                'pf.url_key',
                'pf.price',
                'pf.special_price',
                'pf.special_price_from',
                'pf.special_price_to',
                'pi.path as image_path',
            ])
            ->where('pf.status', 1)
            ->where('pf.visible_individually', 1)
            ->where('pf.channel', $channel)
            ->where('pf.locale', $locale)
            ->where('pf.name', 'LIKE', '%' . $query . '%')
            ->orderByRaw("CASE WHEN pf.name LIKE ? THEN 0 ELSE 1 END", [$query . '%'])
            ->limit($limit)
            ->get();

        $now = Carbon::now();

        $results = $products->map(function ($product) use ($now) {
            $price = (float) $product->price;
            $hasSpecial = false;

            if ($product->special_price) {
                $from = $product->special_price_from ? Carbon::parse($product->special_price_from) : null;
                $to   = $product->special_price_to   ? Carbon::parse($product->special_price_to)   : null;

                if (($from === null || $now->gte($from)) && ($to === null || $now->lte($to))) {
                    $hasSpecial = true;
                    $price = (float) $product->special_price;
                }
            }

            return [
                'id'             => $product->id,
                'name'           => $product->name,
                'url'            => route('shop.product_or_category.index', $product->url_key),
                'image'          => $product->image_path ? Storage::url($product->image_path) : null,
                'price'          => core()->formatPrice($price),
                'original_price' => $hasSpecial ? core()->formatPrice((float) $product->price) : null,
            ];
        });

        return response()->json($results);
    }
}

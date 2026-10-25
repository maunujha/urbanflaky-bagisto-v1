<?php

namespace Webkul\Shop\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status,
            'position' => $this->position,
            'display_mode' => $this->display_mode,
            'card_background' => $this->card_background,
            'description' => $this->description,
            'logo' => $this->when($this->logo_path, fn () => [
                'small_image_url' => url('cache/small/'.$this->logo_path),
                'medium_image_url' => url('cache/medium/'.$this->logo_path),
                'large_image_url' => url('cache/large/'.$this->logo_path),
                'original_image_url' => url('cache/original/'.$this->logo_path),
                'original_image_fallback_url' => ($fallback = webp_fallback_path($this->logo_path))
                    ? url('cache/original/'.$fallback)
                    : null,
                /* Scale-only, card-sized copy for the homepage category cards. */
                'card_image_url' => url('cache/category_card/'.$this->logo_path),
                'card_image_fallback_url' => $fallback
                    ? url('cache/category_card/'.$fallback)
                    : null,
            ]),
            'banner' => $this->when($this->banner_path, fn () => [
                'small_image_url' => url('cache/small/'.$this->banner_path),
                'medium_image_url' => url('cache/medium/'.$this->banner_path),
                'large_image_url' => url('cache/large/'.$this->banner_path),
                'original_image_url' => url('cache/original/'.$this->banner_path),
                'original_image_fallback_url' => ($fallback = webp_fallback_path($this->banner_path))
                    ? url('cache/original/'.$fallback)
                    : null,
            ]),
            'meta' => [
                'title' => $this->meta_title,
                'keywords' => $this->meta_keywords,
                'description' => $this->meta_description,
            ],
            'translations' => $this->translations,
            'additional' => $this->additional,
        ];
    }
}

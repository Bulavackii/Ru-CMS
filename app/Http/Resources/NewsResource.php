<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Modules\News\Models\News
 */
class NewsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'slug' => $this->slug,
            'published' => $this->published,
            'template' => $this->template,
            'price' => $this->price,
            'stock' => $this->stock,
            'is_promo' => $this->is_promo,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Categories\Http\Resources\CategoryResource;

/**
 * @mixin \Modules\Menu\Models\Page
 */
class PageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'published' => $this->published,
            'show_on_homepage' => $this->show_on_homepage,
            'homepage_order' => $this->homepage_order,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

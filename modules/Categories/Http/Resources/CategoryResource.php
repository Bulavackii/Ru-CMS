<?php

namespace Modules\Categories\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 📦 API Resource для категории
 */
class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type,
            'icon' => $this->icon,
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', function () {
                return new CategoryResource($this->parent);
            }),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'usage_counts' => $this->when($request->get('include_usage', false), function () {
                return $this->getUsageCounts();
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->when($this->deleted_at, function () {
                return $this->deleted_at->toIso8601String();
            }),
        ];
    }
}





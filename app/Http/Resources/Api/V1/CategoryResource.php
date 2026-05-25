<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        // Use allChildren (recursive) if loaded, fallback to children
        $childRelation = $this->relationLoaded('allChildren') ? 'allChildren' : 'children';

        return [
            'id' => $this->id,
            'name' => $this->getTranslation('name', $locale),
            'slug' => $this->slug,
            'description' => $this->getTranslation('description', $locale),
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'parentId' => $this->parent_id,
            'productCount' => $this->when(isset($this->products_count), $this->products_count),
            'children' => $this->whenLoaded($childRelation, fn() =>
                CategoryResource::collection($this->$childRelation->where('is_active', true))
            ),
            'sortOrder' => $this->sort_order,
        ];
    }
}


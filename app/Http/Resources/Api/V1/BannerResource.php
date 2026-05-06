<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'title' => $this->getTranslation('title', $locale),
            'subtitle' => $this->getTranslation('subtitle', $locale),
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'linkUrl' => $this->link_url,
            'position' => $this->position,
            'sortOrder' => $this->sort_order,
        ];
    }
}

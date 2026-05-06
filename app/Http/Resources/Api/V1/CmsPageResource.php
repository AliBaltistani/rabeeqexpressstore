<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CmsPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'title' => $this->getTranslation('title', $locale),
            'slug' => $this->slug,
            'content' => $this->getTranslation('content', $locale),
            'excerpt' => $this->getTranslation('excerpt', $locale),
            'featuredImage' => $this->featured_image ? asset('storage/' . $this->featured_image) : null,
            'template' => $this->template,
            'customCss' => $this->custom_css,
            'seo' => [
                'metaTitle' => $this->meta_title,
                'metaDescription' => $this->meta_description,
            ],
        ];
    }
}

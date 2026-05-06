<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'title' => $this->getTranslation('title', $locale),
            'slug' => $this->slug,
            'excerpt' => $this->getTranslation('excerpt', $locale),
            'content' => $this->when($request->routeIs('api.blog.show'), fn() =>
                $this->getTranslation('content', $locale)
            ),
            'featuredImage' => $this->featured_image ? asset('storage/' . $this->featured_image) : null,
            'category' => $this->whenLoaded('category', fn() => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->getTranslation('name', $locale),
                'slug' => $this->category->slug ?? null,
            ] : null),
            'author' => $this->whenLoaded('admin', fn() => $this->admin?->name),
            'viewCount' => $this->view_count,
            'publishedAt' => $this->published_at?->toISOString(),
            'seo' => $this->when($request->routeIs('api.blog.show'), fn() => [
                'metaTitle' => $this->meta_title,
                'metaDescription' => $this->meta_description,
            ]),
        ];
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Currency;
use Illuminate\Http\Request;

class ProductDetailResource extends ProductResource
{
    public function toArray(Request $request): array
    {
        $base = parent::toArray($request);
        $locale = app()->getLocale();
        $currency = $request->query('currency', currency_code());
        $defaultCode = currency_code();
        $currencyObj = Currency::where('code', $currency)->first();
        $symbol = $currencyObj?->symbol ?? $currency;

        return array_merge($base, [
            'shortDescription' => $this->getTranslation('short_description', $locale),
            'description' => $this->getTranslation('description', $locale),
            'weight' => $this->weight,
            'trackStock' => (bool) $this->track_stock,
            'allowBackorders' => (bool) $this->allow_backorders,
            'images' => $this->whenLoaded('images', fn() => $this->images->map(fn($img) => [
                'id' => $img->id,
                'url' => $this->resolveImagePath($img->image_path),
                'altText' => $img->alt_text ?? '',
                'isPrimary' => (bool) ($img->is_primary ?? false),
                'sortOrder' => $img->sort_order ?? 0,
            ])->sortBy('sortOrder')->values()),
            'attributes' => $this->whenLoaded('attributeValues', function () use ($locale) {
                return $this->attributeValues
                    ->groupBy(fn($av) => $av->attribute_id)
                    ->map(function ($values) use ($locale) {
                        $attr = $values->first()->attribute;
                        return [
                            'id' => $attr?->id,
                            'name' => $attr?->getTranslation('name', $locale) ?? '',
                            'values' => $values->map(fn($v) => [
                                'id' => $v->id,
                                'value' => $v->getTranslation('value', $locale),
                            ])->values(),
                        ];
                    })->values();
            }),
            'categoryAttributes' => $this->whenLoaded('category', function () use ($locale) {
                if (!$this->category || !$this->category->relationLoaded('attributes')) {
                    return [];
                }
                return $this->category->attributes->map(function ($attr) use ($locale) {
                    return [
                        'id' => $attr->id,
                        'name' => $attr->getTranslation('name', $locale),
                        'values' => $attr->relationLoaded('values')
                            ? $attr->values->map(fn($v) => [
                                'id' => $v->id,
                                'value' => $v->getTranslation('value', $locale),
                            ])->values()
                            : [],
                    ];
                })->values();
            }),
            'tags' => $this->whenLoaded('tags', fn() => $this->tags->map(fn($tag) => [
                'id' => $tag->id,
                'name' => $tag->getTranslation('name', $locale),
                'slug' => $tag->slug,
            ])),
            'reviews' => $this->whenLoaded('reviews', fn() => [
                'average' => round($this->reviews->avg('rating'), 1),
                'count' => $this->reviews->count(),
                'distribution' => collect([5, 4, 3, 2, 1])->mapWithKeys(fn($star) => [
                    $star => $this->reviews->where('rating', $star)->count(),
                ]),
            ]),
            'seo' => [
                'metaTitle' => $this->meta_title,
                'metaDescription' => $this->meta_description,
                'metaKeywords' => $this->meta_keywords,
            ],
        ]);
    }
}

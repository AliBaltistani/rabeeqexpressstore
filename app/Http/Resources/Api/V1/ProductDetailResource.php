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
                'url' => $img->image ? asset('storage/' . $img->image) : null,
                'altText' => $img->alt_text ?? '',
                'isPrimary' => (bool) ($img->is_primary ?? false),
                'sortOrder' => $img->sort_order ?? 0,
            ])->sortBy('sortOrder')->values()),
            'variants' => $this->whenLoaded('variants', fn() => $this->variants->map(function ($variant) use ($currency, $defaultCode, $symbol) {
                $variantPrice = (float) $variant->price;
                if ($currency !== $defaultCode) {
                    try { $variantPrice = Currency::convert($variantPrice, $defaultCode, $currency); } catch (\Throwable) {}
                }
                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'name' => $variant->name,
                    'price' => [
                        'raw' => round($variantPrice, 2),
                        'formatted' => $symbol . ' ' . number_format($variantPrice, 2),
                    ],
                    'stockQuantity' => $variant->stock_quantity,
                    'inStock' => $variant->stock_quantity > 0,
                    'attributes' => $variant->attribute_values ?? [],
                ];
            })),
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

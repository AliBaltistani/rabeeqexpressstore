<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        // $currency = the currency the user wants to see prices in (from ?currency= query param)
        // $defaultCode = the currency prices are STORED in the DB (store base currency)
        // These are independent — changing the display default does NOT change storage currency.
        $currency = $request->query('currency', currency_code());
        $defaultCode = store_currency_code();

        $price = (float) $this->price;
        $comparePrice = $this->compare_price ? (float) $this->compare_price : null;

        // Convert prices if different currency requested
        if ($currency !== $defaultCode) {
            try {
                $price = Currency::convert($price, $defaultCode, $currency);
                if ($comparePrice) {
                    $comparePrice = Currency::convert($comparePrice, $defaultCode, $currency);
                }
            } catch (\Throwable) {
                // Keep original
            }
        }

        // Flash sale price
        $flashSalePrice = $this->getActiveFlashSalePrice();
        if ($flashSalePrice && $currency !== $defaultCode) {
            try {
                $flashSalePrice = Currency::convert($flashSalePrice, $defaultCode, $currency);
            } catch (\Throwable) {
            }
        }

        // Discount percentage
        $discountPercent = null;
        $effectivePrice = $flashSalePrice ?? $price;
        if ($comparePrice && $comparePrice > $effectivePrice) {
            $discountPercent = round((($comparePrice - $effectivePrice) / $comparePrice) * 100);
        }

        // Currency symbol & decimal places
        $currencyObj = Currency::where('code', $currency)->first();
        $symbol = $currencyObj?->symbol ?? $currency;
        $decimals = $currencyObj?->decimal_places ?? 2;

        // Reviews
        $reviewsLoaded = $this->relationLoaded('reviews');
        $avgRating = $reviewsLoaded ? round($this->reviews->avg('rating'), 1) : null;
        $reviewCount = $reviewsLoaded ? $this->reviews->count() : null;

        return [
            'id' => $this->id,
            'name' => $this->getTranslation('name', $locale),
            'slug' => $this->slug,
            'sku' => $this->sku,
            'price' => [
                'raw' => round($price, $decimals),
                'formatted' => $symbol . ' ' . number_format($price, $decimals),
            ],
            'comparePrice' => $comparePrice ? [
                'raw' => round($comparePrice, $decimals),
                'formatted' => $symbol . ' ' . number_format($comparePrice, $decimals),
            ] : null,
            'flashSalePrice' => $flashSalePrice ? [
                'raw' => round($flashSalePrice, $decimals),
                'formatted' => $symbol . ' ' . number_format($flashSalePrice, $decimals),
            ] : null,
            'discountPercent' => $discountPercent,
            'currency' => $currency,
            'primaryImage' => $this->getPrimaryImage(),
            'images' => $this->getAllImages(),
            'image' => $this->getPrimaryImage(),
            'rating' => $avgRating,
            'reviewCount' => $reviewCount,
            'inStock' => !$this->track_stock || $this->stock_quantity > 0,
            'stockQuantity' => $this->track_stock ? $this->stock_quantity : null,
            'isNew' => (bool) $this->is_new,
            'isFeatured' => (bool) $this->is_featured,
            'category' => $this->whenLoaded('category', fn() => [
                'id' => $this->category->id,
                'name' => $this->category->getTranslation('name', $locale),
                'slug' => $this->category->slug,
            ]),
            'brand' => $this->whenLoaded('brand', fn() => $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ] : null),
        ];
    }

    /**
     * Get all product images with resolved URLs.
     */
    protected function getAllImages(): array
    {
        if (!$this->relationLoaded('images') || $this->images->isEmpty()) {
            $primary = $this->getPrimaryImage();
            return $primary ? [['id' => 0, 'url' => $primary, 'alt' => null, 'isPrimary' => true, 'sortOrder' => 0]] : [];
        }

        return $this->images
            ->sortBy('sort_order')
            ->values()
            ->map(fn($img) => [
                'id' => $img->id,
                'url' => $this->resolveImagePath($img->image_path),
                'alt' => $img->alt_text ?? null,
                'isPrimary' => (bool) $img->is_primary,
                'sortOrder' => $img->sort_order ?? 0,
            ])
            ->toArray();
    }

    /**
     * Resolve a single image path to a full URL.
     */
    protected function resolveImagePath(?string $path): string
    {
        $placeholder = asset('storage/dummy/placeholder.jpg');
        if (empty($path)) return $placeholder;

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        if (\Illuminate\Support\Facades\Storage::exists($path)) {
            try {
                return \Illuminate\Support\Facades\Storage::temporaryUrl($path, now()->addDay());
            } catch (\Throwable) {
                return asset('storage/' . $path);
            }
        }

        return $placeholder;
    }



    /**
     * Get the primary image URL.
     */
    protected function getPrimaryImage(): ?string
    {
        $placeholder = asset('storage/dummy/placeholder.jpg');

        if ($this->relationLoaded('images') && $this->images->isNotEmpty()) {
            $primary = $this->images->where('is_primary', true)->first() ?? $this->images->first();
            $path = $primary->image_path ?? null;

            if (empty($path)) return $placeholder;

            if (filter_var($path, FILTER_VALIDATE_URL)) {
                return $path;
            }

            // Check public disk first
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                return asset('storage/' . $path);
            }

            // Fall back to local disk with signed URL
            if (\Illuminate\Support\Facades\Storage::exists($path)) {
                try {
                    return \Illuminate\Support\Facades\Storage::temporaryUrl($path, now()->addDay());
                } catch (\Throwable) {
                    return asset('storage/' . $path);
                }
            }

            return $placeholder;
        }

        return $placeholder;
    }
}

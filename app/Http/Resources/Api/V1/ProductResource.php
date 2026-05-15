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
        $currency = $request->query('currency', currency_code());
        $defaultCode = currency_code();

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

        // Currency symbol
        $currencyObj = Currency::where('code', $currency)->first();
        $symbol = $currencyObj?->symbol ?? $currency;

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
                'raw' => round($price, 2),
                'formatted' => $symbol . ' ' . number_format($price, 2),
            ],
            'comparePrice' => $comparePrice ? [
                'raw' => round($comparePrice, 2),
                'formatted' => $symbol . ' ' . number_format($comparePrice, 2),
            ] : null,
            'flashSalePrice' => $flashSalePrice ? [
                'raw' => round($flashSalePrice, 2),
                'formatted' => $symbol . ' ' . number_format($flashSalePrice, 2),
            ] : null,
            'discountPercent' => $discountPercent,
            'currency' => $currency,
            'primaryImage' => $this->getPrimaryImage(),
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
     * Get the active flash sale price for this product.
     */
    protected function getActiveFlashSalePrice(): ?float
    {
        if (!$this->relationLoaded('flashSales')) {
            return null;
        }

        $now = now();
        foreach ($this->flashSales as $flashSale) {
            if ($flashSale->is_active && $flashSale->starts_at <= $now && $flashSale->ends_at >= $now) {
                $pivot = $flashSale->pivot ?? null;
                // Check if there's a flash_sale_products entry
                $fsp = \App\Models\FlashSaleProduct::where('flash_sale_id', $flashSale->id)
                    ->where('product_id', $this->id)
                    ->first();
                if ($fsp) {
                    return (float) $fsp->sale_price;
                }
            }
        }
        return null;
    }

    /**
     * Get the primary image URL.
     */
    protected function getPrimaryImage(): ?string
    {
        $placeholder = asset('storage/dummy/placeholder.jpg');

        if ($this->relationLoaded('images') && $this->images->isNotEmpty()) {
            $primary = $this->images->where('is_primary', true)->first() ?? $this->images->first();

            return $primary->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($primary->image)
                ? asset('storage/' . $primary->image)
                : $placeholder;
        }

        return $placeholder;
    }
}

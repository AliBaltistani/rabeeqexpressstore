<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * CartResource wraps a collection of CartItem models + computed totals.
     * Pass as: new CartResource((object) ['items' => ..., 'subtotal' => ..., ...])
     */
    public function toArray(Request $request): array
    {
        $code = $request->query('currency', currency_code());
        $currencyObj = Currency::where('code', $code)->first();
        $symbol = $currencyObj?->symbol ?? $code;
        $locale = app()->getLocale();
        $defaultCode = currency_code();

        $items = collect($this->items ?? []);

        $mappedItems = $items->map(function ($item) use ($locale, $code, $defaultCode, $symbol) {
            $product = $item->product;
            $price = (float) ($item->variant?->price ?? $product?->price ?? 0);

            if ($code !== $defaultCode) {
                try { $price = Currency::convert($price, $defaultCode, $code); } catch (\Throwable) {}
            }

            $lineTotal = $price * $item->quantity;

            return [
                'id' => $item->id,
                'productId' => $item->product_id,
                'variantId' => $item->variant_id,
                'productName' => $product?->getTranslation('name', $locale) ?? '',
                'productSlug' => $product?->slug,
                'variantName' => $item->variant?->name,
                'image' => $this->getProductImage($product),
                'quantity' => $item->quantity,
                'unitPrice' => [
                    'raw' => round($price, 2),
                    'formatted' => $symbol . ' ' . number_format($price, 2),
                ],
                'lineTotal' => [
                    'raw' => round($lineTotal, 2),
                    'formatted' => $symbol . ' ' . number_format($lineTotal, 2),
                ],
                'inStock' => !$product?->track_stock || ($product?->stock_quantity ?? 0) > 0,
            ];
        });

        $subtotal = $this->subtotal ?? 0;
        $discount = $this->discountAmount ?? 0;
        $shipping = $this->shippingAmount ?? 0;
        $total = $subtotal - $discount + $shipping;

        if ($code !== $defaultCode) {
            try {
                $subtotal = Currency::convert($subtotal, $defaultCode, $code);
                $discount = Currency::convert($discount, $defaultCode, $code);
                $shipping = Currency::convert($shipping, $defaultCode, $code);
                $total = $subtotal - $discount + $shipping;
            } catch (\Throwable) {}
        }

        return [
            'items' => $mappedItems,
            'itemCount' => $items->sum('quantity'),
            'subtotal' => [
                'raw' => round($subtotal, 2),
                'formatted' => $symbol . ' ' . number_format($subtotal, 2),
            ],
            'discountAmount' => [
                'raw' => round($discount, 2),
                'formatted' => $symbol . ' ' . number_format($discount, 2),
            ],
            'couponCode' => $this->couponCode ?? null,
            'shippingAmount' => [
                'raw' => round($shipping, 2),
                'formatted' => $symbol . ' ' . number_format($shipping, 2),
            ],
            'total' => [
                'raw' => round($total, 2),
                'formatted' => $symbol . ' ' . number_format($total, 2),
            ],
            'currency' => $code,
        ];
    }

    protected function getProductImage($product): ?string
    {
        if (!$product || !$product->relationLoaded('images')) {
            return null;
        }
        $primary = $product->images->where('is_primary', true)->first() ?? $product->images->first();
        $path = $primary?->image_path;

        if (empty($path)) return asset('storage/dummy/placeholder.jpg');

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

        return asset('storage/dummy/placeholder.jpg');
    }
}

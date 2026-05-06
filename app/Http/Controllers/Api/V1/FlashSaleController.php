<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Traits\ApiResponse;
use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use Illuminate\Http\JsonResponse;

class FlashSaleController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/flash-sales/active
     */
    public function active(): JsonResponse
    {
        $locale = app()->getLocale();
        $now = now();

        $flashSale = FlashSale::where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->first();

        if (!$flashSale) {
            return $this->success(null, 'No active flash sale.');
        }

        $flashProducts = FlashSaleProduct::where('flash_sale_id', $flashSale->id)
            ->with(['product' => fn($q) => $q->with(['category', 'brand', 'images', 'reviews'])])
            ->get();

        return $this->success([
            'id' => $flashSale->id,
            'name' => $flashSale->getTranslation('name', $locale),
            'startsAt' => $flashSale->starts_at->toISOString(),
            'endsAt' => $flashSale->ends_at->toISOString(),
            'products' => $flashProducts->map(fn($fp) => [
                'product' => $fp->product ? new ProductResource($fp->product) : null,
                'salePrice' => (float) $fp->sale_price,
                'originalPrice' => (float) $fp->original_price,
                'quantityLimit' => $fp->quantity_limit,
                'soldCount' => $fp->sold_count,
                'discountPercent' => $fp->original_price > 0
                    ? round((($fp->original_price - $fp->sale_price) / $fp->original_price) * 100)
                    : 0,
            ])->filter(fn($fp) => $fp['product'] !== null)->values(),
        ]);
    }
}

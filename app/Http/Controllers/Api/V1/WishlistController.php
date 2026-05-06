<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Traits\ApiResponse;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/wishlist
     */
    public function index(Request $request): JsonResponse
    {
        $wishlists = Wishlist::where('user_id', $request->user()->id)
            ->with(['product' => fn($q) => $q->with(['category', 'brand', 'images', 'reviews', 'flashSales'])])
            ->get();

        $products = $wishlists->map(fn($w) => $w->product)->filter();

        return $this->success(ProductResource::collection($products));
    }

    /**
     * POST /api/v1/wishlist
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'productId' => ['required', 'exists:products,id'],
        ]);

        $exists = Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $validated['productId'])
            ->exists();

        if ($exists) {
            return $this->success(null, 'Product already in wishlist.');
        }

        Wishlist::create([
            'user_id' => $request->user()->id,
            'product_id' => $validated['productId'],
        ]);

        return $this->success(null, 'Added to wishlist.', 201);
    }

    /**
     * DELETE /api/v1/wishlist/{productId}
     */
    public function destroy(Request $request, int $productId): JsonResponse
    {
        Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $productId)
            ->delete();

        return $this->success(null, 'Removed from wishlist.');
    }
}

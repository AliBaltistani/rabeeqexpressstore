<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductDetailResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Traits\ApiResponse;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/products
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('perPage', setting('general.products_per_page', 12)), 48);

        $query = Product::query()
            ->with(['category', 'brand', 'images', 'reviews', 'flashSales'])
            ->withCount('reviews');

        // Filters
        $this->applyFilters($query, $request);

        // Sorting
        $this->applySorting($query, $request);

        $products = $query->paginate($perPage);

        return $this->paginated($products, ProductResource::class);
    }

    /**
     * GET /api/v1/products/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->with([
                'category', 'brand', 'images', 'variants',
                'tags', 'reviews' => fn($q) => $q->where('status', 'approved'),
                'flashSales',
            ])
            ->first();

        if (!$product) {
            return $this->notFound('Product not found.');
        }

        return $this->success(new ProductDetailResource($product));
    }

    /**
     * GET /api/v1/products/featured
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 8), 24);

        $products = Product::where('is_featured', true)
            ->with(['category', 'brand', 'images', 'reviews', 'flashSales'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $this->success(ProductResource::collection($products));
    }

    /**
     * GET /api/v1/products/new-arrivals
     */
    public function newArrivals(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 8), 24);

        $products = Product::with(['category', 'brand', 'images', 'reviews', 'flashSales'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $this->success(ProductResource::collection($products));
    }

    /**
     * GET /api/v1/products/best-sellers
     */
    public function bestSellers(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 8), 24);

        $products = Product::with(['category', 'brand', 'images', 'reviews', 'flashSales'])
            ->withCount('orderItems')
            ->orderByDesc('order_items_count')
            ->limit($limit)
            ->get();

        return $this->success(ProductResource::collection($products));
    }

    /**
     * GET /api/v1/products/search?q=term
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query('q', '');
        if (strlen($query) < 2) {
            return $this->success([]);
        }

        $perPage = min((int) $request->query('perPage', 12), 48);

        $products = Product::with(['category', 'brand', 'images', 'reviews', 'flashSales'])
            ->where(function (Builder $q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('sku', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%")
                  ->orWhere('short_description', 'LIKE', "%{$query}%");
            })
            ->paginate($perPage);

        return $this->paginated($products, ProductResource::class);
    }

    protected function applyFilters(Builder $query, Request $request): void
    {
        if ($category = $request->query('category')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $category));
        }

        if ($brand = $request->query('brand')) {
            $query->whereHas('brand', fn($q) => $q->where('slug', $brand));
        }

        if ($tags = $request->query('tags')) {
            $tagSlugs = explode(',', $tags);
            $query->whereHas('tags', fn($q) => $q->whereIn('slug', $tagSlugs));
        }

        if ($search = $request->query('search')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('sku', 'LIKE', "%{$search}%");
            });
        }

        if ($ids = $request->query('ids')) {
            $idArray = is_array($ids) ? $ids : explode(',', $ids);
            $query->whereIn('id', $idArray);
        }

        if ($minPrice = $request->query('minPrice')) {
            $query->where('price', '>=', (float) $minPrice);
        }

        if ($maxPrice = $request->query('maxPrice')) {
            $query->where('price', '<=', (float) $maxPrice);
        }

        if ($rating = $request->query('rating')) {
            $query->whereHas('reviews', function ($q) use ($rating) {
                $q->where('status', 'approved');
            }, '>=', 1)
            ->withAvg(['reviews' => fn($q) => $q->where('status', 'approved')], 'rating')
            ->having('reviews_avg_rating', '>=', (int) $rating);
        }
    }

    protected function applySorting(Builder $query, Request $request): void
    {
        $sortBy = $request->query('sortBy', 'newest');

        match ($sortBy) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'best_seller' => $query->withCount('orderItems')->orderByDesc('order_items_count'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            default => $query->orderByDesc('created_at'), // newest
        };
    }
}

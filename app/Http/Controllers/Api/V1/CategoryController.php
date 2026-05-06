<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Traits\ApiResponse;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/categories — tree of active categories
     */
    public function index(): JsonResponse
    {
        $categories = Category::whereNull('parent_id')
            ->with(['children' => fn($q) => $q->withCount('products')->orderBy('sort_order')])
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();

        return $this->success(CategoryResource::collection($categories));
    }

    /**
     * GET /api/v1/categories/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)
            ->with(['children' => fn($q) => $q->withCount('products')->orderBy('sort_order'), 'parent'])
            ->withCount('products')
            ->first();

        if (!$category) {
            return $this->notFound('Category not found.');
        }

        return $this->success(new CategoryResource($category));
    }
}

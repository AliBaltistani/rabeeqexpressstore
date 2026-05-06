<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BrandResource;
use App\Http\Traits\ApiResponse;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/brands
     */
    public function index(): JsonResponse
    {
        $brands = Brand::where('is_active', true)
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();

        return $this->success(BrandResource::collection($brands));
    }
}

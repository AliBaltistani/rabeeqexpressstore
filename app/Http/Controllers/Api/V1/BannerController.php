<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BannerResource;
use App\Http\Traits\ApiResponse;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/banners?position=hero
     */
    public function index(Request $request): JsonResponse
    {
        $query = Banner::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('sort_order');

        if ($position = $request->query('position')) {
            $query->where('position', $position);
        }

        return $this->success(BannerResource::collection($query->get()));
    }
}

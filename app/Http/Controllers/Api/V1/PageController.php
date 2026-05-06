<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CmsPageResource;
use App\Http\Traits\ApiResponse;
use App\Models\CmsPage;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/pages/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        $page = CmsPage::where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if (!$page) {
            return $this->notFound('Page not found.');
        }

        return $this->success(new CmsPageResource($page));
    }
}

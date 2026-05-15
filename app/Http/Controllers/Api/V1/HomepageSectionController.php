<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BannerResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Traits\ApiResponse;
use App\Models\HomepageSection;
use Illuminate\Http\JsonResponse;

class HomepageSectionController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/homepage-sections
     *
     * Returns all active homepage sections ordered by sort_order,
     * each with resolved related data based on section type.
     */
    public function index(): JsonResponse
    {
        $sections = HomepageSection::active()->get();

        $result = $sections->map(function (HomepageSection $section) {
            $config = $section->config ?? [];

            $base = [
                'id' => $section->id,
                'name' => $section->name,
                'type' => $section->type,
                'sort_order' => $section->sort_order,
                'config' => $this->sanitizeConfig($config, $section->type),
            ];

            switch ($section->type) {
                case 'hero_slider':
                    $sliders = $section->getResolvedSliders();
                    $base['data'] = $sliders->map(fn($s) => [
                        'id' => $s->id,
                        'title' => $s->getTranslation('title', app()->getLocale()),
                        'image' => $s->image ? asset('storage/' . $s->image) : null,
                        'link_url' => $s->link_url,
                    ])->values();
                    break;

                case 'banner':
                    $banners = $section->getResolvedBanners();
                    $base['data'] = BannerResource::collection($banners);
                    break;

                case 'products':
                    $products = $section->getResolvedProducts();
                    $base['data'] = ProductResource::collection($products);
                    break;

                case 'custom_html':
                    $base['data'] = [
                        'content' => $config['content'] ?? '',
                    ];
                    break;

                default:
                    $base['data'] = [];
            }

            return $base;
        });

        return $this->success($result);
    }

    /**
     * Sanitize config to only send frontend-relevant keys (strip internal IDs).
     */
    private function sanitizeConfig(array $config, string $type): array
    {
        $safe = [];

        switch ($type) {
            case 'hero_slider':
                foreach ([
                    'show_indicators',
                    'indicator_position',
                    'navigation_style',
                    'navigation_position',
                    'height',
                    'width',
                    'autoplay',
                    'autoplay_delay',
                ] as $key) {
                    if (isset($config[$key]))
                        $safe[$key] = $config[$key];
                }
                break;

            case 'banner':
                foreach (['cols', 'rows', 'gap', 'position'] as $key) {
                    if (isset($config[$key]))
                        $safe[$key] = $config[$key];
                }
                break;

            case 'products':
                foreach ([
                    'title_en',
                    'title_ar',
                    'cols',
                    'show_price',
                    'show_badge',
                    'show_add_to_cart',
                ] as $key) {
                    if (isset($config[$key]))
                        $safe[$key] = $config[$key];
                }
                break;

            case 'custom_html':
                foreach (['title_en', 'title_ar', 'content'] as $key) {
                    if (isset($config[$key]))
                        $safe[$key] = $config[$key];
                }
                break;
        }

        return $safe;
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BannerResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Traits\ApiResponse;
use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class HomeSectionController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/homepage-sections
     *
     * Returns all active homepage sections with resolved data + config.
     */
    public function index(): JsonResponse
    {
        $sections = HomeSection::active()->get();

        $result = $sections->map(function (HomeSection $section) {
            $locale = app()->getLocale();
            $config = $section->config ?? [];

            $base = [
                'id' => $section->id,
                'type' => $section->type,
                'title' => $section->getTranslation('title', $locale),
                'sort_order' => $section->sort_order,
                'config' => $this->sanitizeConfig($config, $section->type),
            ];

            switch ($section->type) {
                case 'hero_slider':
                    $banners = $this->getActiveBanners('hero');
                    $base['data'] = BannerResource::collection($banners);
                    break;

                case 'promo_banners':
                    $maxBanners = $config['max_banners'] ?? 4;
                    $banners = $this->getActiveBanners('promo')->take($maxBanners);
                    $base['data'] = BannerResource::collection($banners);
                    break;

                case 'featured_products':
                    $base['data'] = ProductResource::collection(
                        $this->queryProducts(
                            Product::where('is_featured', true),
                            $config
                        )
                    );
                    break;

                case 'best_sellers':
                    $base['data'] = ProductResource::collection(
                        $this->queryProducts(
                            Product::query()->withCount('orderItems')->orderByDesc('order_items_count'),
                            $config,
                            false // skip default ordering since we use order_items_count
                        )
                    );
                    break;

                case 'new_arrivals':
                    $base['data'] = ProductResource::collection(
                        $this->queryProducts(Product::query(), $config)
                    );
                    break;

                case 'category_products':
                    $categorySlug = $config['category_slug'] ?? null;
                    if ($categorySlug) {
                        $category = Category::where('slug', $categorySlug)->first();
                        if ($category) {
                            $base['data'] = ProductResource::collection(
                                $this->queryProducts(
                                    Product::where('category_id', $category->id),
                                    $config
                                )
                            );
                            $base['category'] = [
                                'name' => $category->getTranslation('name', $locale),
                                'slug' => $category->slug,
                            ];
                        } else {
                            $base['data'] = [];
                        }
                    } else {
                        $base['data'] = [];
                    }
                    break;

                case 'reviews':
                    $base['data'] = $config['reviews'] ?? [];
                    break;

                default:
                    $base['data'] = [];
            }

            return $base;
        });

        return $this->success($result);
    }

    /**
     * Query products with shared pagination/sorting logic.
     */
    private function queryProducts($query, array $config, bool $applyDefaultSort = true)
    {
        $limit = min($config['limit'] ?? 10, 50);

        $query->with(['category', 'brand', 'images', 'reviews', 'flashSales']);

        // Apply sort
        $sortBy = $config['sort_by'] ?? 'default';
        if ($applyDefaultSort) {
            switch ($sortBy) {
                case 'price_low':
                    $query->orderBy('price');
                    break;
                case 'price_high':
                    $query->orderByDesc('price');
                    break;
                case 'rating':
                    $query->withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating');
                    break;
                case 'newest':
                default:
                    $query->orderByDesc('created_at');
                    break;
            }
        }

        return $query->limit($limit)->get();
    }

    /**
     * Sanitize config to only send frontend-relevant keys (not review content, etc.).
     */
    private function sanitizeConfig(array $config, string $type): array
    {
        $safe = [];

        // Common appearance
        foreach (['subtitle_en', 'subtitle_ar', 'css_class', 'background', 'padding'] as $key) {
            if (isset($config[$key])) $safe[$key] = $config[$key];
        }

        // Type-specific
        switch ($type) {
            case 'hero_slider':
                foreach (['autoplay', 'autoplay_speed', 'show_navigation', 'show_pagination'] as $key) {
                    if (isset($config[$key])) $safe[$key] = $config[$key];
                }
                break;

            case 'promo_banners':
                foreach (['columns', 'max_banners', 'gap'] as $key) {
                    if (isset($config[$key])) $safe[$key] = $config[$key];
                }
                break;

            case 'featured_products':
            case 'best_sellers':
            case 'new_arrivals':
            case 'category_products':
                foreach (['layout', 'products_per_row', 'show_view_all', 'view_all_url'] as $key) {
                    if (isset($config[$key])) $safe[$key] = $config[$key];
                }
                break;

            case 'reviews':
                foreach (['review_layout', 'reviews_per_view', 'show_rating_stars', 'show_avatars'] as $key) {
                    if (isset($config[$key])) $safe[$key] = $config[$key];
                }
                break;
        }

        return $safe;
    }

    /**
     * Get active banners filtered by position.
     */
    private function getActiveBanners(string $position)
    {
        return Banner::where('is_active', true)
            ->where('position', $position)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('sort_order')
            ->get();
    }
}

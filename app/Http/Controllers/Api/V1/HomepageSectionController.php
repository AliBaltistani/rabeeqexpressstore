<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BannerResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Traits\ApiResponse;
use App\Models\HomepageSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

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
                'id'         => $section->id,
                'name'       => $section->name,
                'type'       => $section->type,
                'sort_order' => $section->sort_order,
                'config'     => $this->sanitizeConfig($config, $section->type),
            ];

            switch ($section->type) {
                case 'hero_slider':
                    $sliders = $section->getResolvedSliders();
                    $base['data'] = $sliders->map(fn($s) => [
                        'id'       => $s->id,
                        'title'    => $s->getTranslation('title', app()->getLocale()),
                        'image'    => $this->resolveImageUrl($s->image),
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

                case 'reviews':
                    $manual_reviews = collect($config['reviews'] ?? [])->map(function ($r) {
                        $name = $r['name'] ?? 'User';
                        $avatarUrl = !empty($r['avatar']) ? $this->resolveImageUrl($r['avatar']) : null;
                        
                        return [
                            'name' => $name,
                            'rating' => (int) ($r['rating'] ?? 5),
                            'avatar' => $avatarUrl ?: 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&color=7F9CF5&background=EBF4FF',
                            'text' => $r['text'] ?? '',
                        ];
                    })->toArray();

                    $selected_review_ids = $config['selected_reviews'] ?? [];
                    $db_reviews = [];
                    if (!empty($selected_review_ids)) {
                        $db_reviews = \App\Models\Review::query()
                            ->with('user')
                            ->whereIn('id', $selected_review_ids)
                            ->get()
                            ->map(function ($r) {
                                $name = $r->user ? $r->user->name : 'Unknown User';
                                $avatarUrl = ($r->user && $r->user->avatar) ? $this->resolveImageUrl($r->user->avatar) : null;
                                    
                                return [
                                    'name' => $name,
                                    'rating' => (int) $r->rating,
                                    'avatar' => $avatarUrl ?: 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&color=7F9CF5&background=EBF4FF',
                                    'text' => $r->body,
                                ];
                            })->toArray();
                    }

                    $base['data'] = array_merge($db_reviews, $manual_reviews);
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
     * Resolve an image path to a full URL.
     * Uses temporaryUrl for local disk (signed URLs), asset() for public disk.
     */
    private function resolveImageUrl(?string $path): ?string
    {
        if (empty($path)) return null;

        // Try public disk first
        if (Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        // Fall back to default (local) disk with signed temporary URL
        if (Storage::exists($path)) {
            return Storage::temporaryUrl($path, now()->addDay());
        }

        return null;
    }

    /**
     * Sanitize config to only send frontend-relevant keys (strip internal IDs).
     */
    private function sanitizeConfig(array $config, string $type): array
    {
        // Common keys available to all section types
        $commonKeys = [
            'section_width',
            'title_alignment', 'show_title',
            'arrows_style', 'arrows_position', 'show_arrows',
            'custom_css', 'custom_js',
        ];

        $safe = [];

        switch ($type) {
            case 'hero_slider':
                foreach ([
                    'show_indicators', 'indicator_position',
                    'navigation_style', 'navigation_position',
                    'height', 'width', 'autoplay', 'autoplay_delay',
                ] as $key) {
                    if (isset($config[$key])) $safe[$key] = $config[$key];
                }
                break;

            case 'banner':
                foreach (['cols', 'rows', 'gap', 'position'] as $key) {
                    if (isset($config[$key])) $safe[$key] = $config[$key];
                }
                break;

            case 'products':
                foreach ([
                    'title_en', 'title_ar', 'cols',
                    'show_price', 'show_badge', 'show_add_to_cart',
                    'display_mode', 'autoplay', 'autoplay_delay',
                    'slides_per_view', 'loop', 'direction',
                ] as $key) {
                    if (isset($config[$key])) $safe[$key] = $config[$key];
                }
                break;

            case 'reviews':
                foreach ([
                    'title_en', 'title_ar',
                    'display_mode', 'autoplay', 'autoplay_delay',
                    'slides_per_view', 'loop', 'direction',
                    'show_rating_stars', 'show_avatars',
                ] as $key) {
                    if (isset($config[$key])) $safe[$key] = $config[$key];
                }
                break;

            case 'custom_html':
                foreach (['title_en', 'title_ar', 'content'] as $key) {
                    if (isset($config[$key])) $safe[$key] = $config[$key];
                }
                break;
        }

        // Merge common keys
        foreach ($commonKeys as $key) {
            if (isset($config[$key])) $safe[$key] = $config[$key];
        }

        return $safe;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Model;

class HomepageSection extends Model
{
    use HasFactory;

    protected $table = 'homepage_sections';

    protected $fillable = [
        'name',
        'type',
        'sort_order',
        'is_active',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
    ];

    /**
     * Available section types.
     */
    public const TYPES = [
        'hero_slider' => 'Hero Slider',
        'banner' => 'Banner',
        'products' => 'Products',
        'custom_html' => 'Custom HTML',
    ];

    /**
     * Scope: active sections ordered by sort_order.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Get resolved sliders from config.slider_ids.
     */
    public function getResolvedSliders()
    {
        $ids = $this->config['slider_ids'] ?? [];
        if (empty($ids))
            return collect();

        return Slider::whereIn('id', $ids)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get resolved banners from config.banner_ids.
     */
    public function getResolvedBanners()
    {
        $ids = $this->config['banner_ids'] ?? [];
        if (empty($ids))
            return collect();

        return Banner::whereIn('id', $ids)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get resolved products from config.product_ids.
     */
    public function getResolvedProducts()
    {
        $ids = $this->config['product_ids'] ?? [];
        if (empty($ids))
            return collect();

        return Product::withoutGlobalScope('active')
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->with(['category', 'brand', 'images', 'reviews', 'flashSales'])
            ->get();
    }
}

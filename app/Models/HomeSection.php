<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;
use App\Models\Model;

class HomeSection extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['title'];

    protected $fillable = [
        'type',
        'title',
        'is_active',
        'sort_order',
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
        'promo_banners' => 'Promo Banners (Grid)',
        'featured_products' => 'Featured Products',
        'best_sellers' => 'Best Sellers',
        'new_arrivals' => 'New Arrivals',
        'category_products' => 'Category Products',
        'reviews' => 'Customer Reviews',
    ];

    /**
     * Scope: active sections ordered by sort.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}

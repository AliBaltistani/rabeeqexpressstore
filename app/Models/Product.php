<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;
use App\Models\Model;

class Product extends Model
{
    use HasFactory, SoftDeletes, HasTranslations, HasSlug;

    public array $translatable = ['name', 'short_description', 'description'];

    protected $fillable = [
        'sku',
        'slug',
        'category_id',
        'brand_id',
        'product_type',
        'price',
        'compare_price',
        'cost_price',
        'stock_quantity',
        'low_stock_threshold',
        'track_stock',
        'allow_backorders',
        'weight',
        'is_active',
        'is_featured',
        'is_new',
        'sort_order',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'name',
        'short_description',
        'description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'track_stock' => 'boolean',
        'allow_backorders' => 'boolean',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('is_active', true);
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tags');
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductAttributeValue::class,
            'product_attribute_value_product',
            'product_id',
            'attribute_value_id'
        );
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function flashSales(): BelongsToMany
    {
        return $this->belongsToMany(FlashSale::class, 'flash_sale_products');
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Get the active flash sale price for this product, optionally scoped to a variant.
     */
    public function getActiveFlashSalePrice(?int $variantId = null): ?float
    {
        if (!$this->relationLoaded('flashSales')) {
            $this->load('flashSales');
        }

        $now = now();
        foreach ($this->flashSales as $flashSale) {
            if ($flashSale->is_active && $flashSale->starts_at <= $now && $flashSale->ends_at >= $now) {
                $query = \App\Models\FlashSaleProduct::where('flash_sale_id', $flashSale->id)
                    ->where('product_id', $this->id);
                
                if ($variantId) {
                    $query->where('variant_id', $variantId);
                }

                $fsp = $query->first();

                if ($fsp) {
                    return (float) $fsp->sale_price;
                }
            }
        }
        return null;
    }
}


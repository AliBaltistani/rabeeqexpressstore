<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;
use App\Models\Model;


class Category extends Model
{
    use HasFactory, SoftDeletes, HasTranslations, HasSlug;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'parent_id',
        'slug',
        'image',
        'is_active',
        'sort_order',
        'name',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    // ── Direct parent ──
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // ── Direct children ──
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // ── Recursive children (unlimited depth) ──
    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    // ── Ancestors chain (bottom-up) ──
    public function ancestors(): Collection
    {
        $ancestors = collect();
        $current = $this->parent;
        while ($current) {
            $ancestors->prepend($current);
            $current = $current->parent;
        }
        return $ancestors;
    }

    // ── Depth level (0 = root) ──
    public function getDepthAttribute(): int
    {
        return $this->ancestors()->count();
    }

    /**
     * Indented name for admin selectors: "Root > Child > Grandchild"
     */
    public function getIndentedName(string $locale = 'en', string $separator = ' > '): string
    {
        $parts = $this->ancestors()->map(fn(Category $a) => $a->getTranslation('name', $locale));
        $parts->push($this->getTranslation('name', $locale));
        return $parts->implode($separator);
    }

    /**
     * Get all descendant IDs (recursive) for circular reference validation.
     */
    public function getAllDescendantIds(): Collection
    {
        $ids = collect();
        $children = static::withoutGlobalScope('active')
            ->where('parent_id', $this->id)
            ->get(['id']);

        foreach ($children as $child) {
            $ids->push($child->id);
            $ids = $ids->merge($child->getAllDescendantIds());
        }

        return $ids;
    }

    /**
     * Build flat options array for Filament selects with depth indicators.
     * Returns: [id => "── ── Category Name", ...]
     */
    public static function getHierarchicalOptions(?int $excludeId = null, string $locale = 'en'): array
    {
        $categories = static::withoutGlobalScope('active')
            ->whereNull('parent_id')
            ->with('allChildren')
            ->orderBy('sort_order')
            ->get();

        $options = [];
        static::flattenTree($categories, $options, 0, $excludeId, $locale);
        return $options;
    }

    private static function flattenTree(
        $categories,
        array &$options,
        int $depth,
        ?int $excludeId,
        string $locale
    ): void {
        foreach ($categories as $category) {
            if ($excludeId && $category->id === $excludeId) {
                continue;
            }
            $prefix = $depth > 0 ? str_repeat('── ', $depth) : '';
            $options[$category->id] = $prefix . $category->getTranslation('name', $locale);

            if ($category->relationLoaded('allChildren') && $category->allChildren->count()) {
                static::flattenTree(
                    $category->allChildren->sortBy('sort_order'),
                    $options,
                    $depth + 1,
                    $excludeId,
                    $locale
                );
            }
        }
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(ProductAttribute::class, 'category_attributes', 'category_id', 'attribute_id')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }
}

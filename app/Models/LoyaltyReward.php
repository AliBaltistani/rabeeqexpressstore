<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;
use App\Models\Model;

class LoyaltyReward extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'slug',
        'type',
        'name',
        'description',
        'points_cost',
        'discount_value',
        'discount_type',
        'image',
        'applicable_shipping_method_ids',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'points_cost'                    => 'integer',
        'discount_value'                 => 'decimal:2',
        'is_active'                      => 'boolean',
        'sort_order'                     => 'integer',
        'applicable_shipping_method_ids' => 'array',
    ];

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ── Relationships ──

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    // ── Helpers ──

    public function isDiscount(): bool
    {
        return $this->type === 'discount';
    }

    public function isFreeShipping(): bool
    {
        return $this->type === 'free_shipping';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Model;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'max_discount_amount',
        'min_order_amount',
        'usage_limit',
        'usage_limit_per_user',
        'usage_count',
        'applies_to',
        'exclude_sale_items',
        'is_active',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'exclude_sale_items' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_products');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'coupon_categories');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function isValidForUser(User $user): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($this->usage_limit_per_user) {
            $userUsageCount = $this->usages()
                ->where('user_id', $user->id)
                ->count();

            if ($userUsageCount >= $this->usage_limit_per_user) {
                return false;
            }
        }

        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if (!$this->isValid()) {
            return 0;
        }

        $discount = match ($this->type) {
            'percentage' => ($amount * $this->value) / 100,
            'fixed' => $this->value,
            'free_shipping' => 0,
            default => 0,
        };

        if ($this->max_discount_amount) {
            $discount = min($discount, $this->max_discount_amount);
        }

        return $discount;
    }
}

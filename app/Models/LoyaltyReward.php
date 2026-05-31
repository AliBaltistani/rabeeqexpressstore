<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'points_cost'    => 'integer',
        'discount_value' => 'decimal:2',
        'is_active'      => 'boolean',
        'sort_order'     => 'integer',
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
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;

class ShippingMethod extends Model
{
    use HasFactory, HasTranslations;

    /** @var array<string> */
    public array $translatable = ['name', 'description'];

    /** @var array<string> */
    protected $fillable = [
        'slug',
        'name',
        'description',
        'base_cost',
        'carrier_type',
        'is_active',
        'supported_countries',
        'estimated_days_min',
        'estimated_days_max',
        'sort_order',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'base_cost'             => 'decimal:2',
        'is_active'             => 'boolean',
        'supported_countries'   => 'array',
        'estimated_days_min'    => 'integer',
        'estimated_days_max'    => 'integer',
        'sort_order'            => 'integer',
    ];

    // ─── Scopes ───

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForCountry(Builder $query, string $countryCode): Builder
    {
        return $query->where(function (Builder $q) use ($countryCode) {
            $q->whereNull('supported_countries')
              ->orWhereJsonContains('supported_countries', strtoupper($countryCode));
        });
    }

    public function scopeByCarrier(Builder $query, string $carrierType): Builder
    {
        return $query->where('carrier_type', $carrierType);
    }

    // ─── Helpers ───

    public function getEstimatedDeliveryLabel(): string
    {
        if ($this->estimated_days_min && $this->estimated_days_max) {
            return "{$this->estimated_days_min}–{$this->estimated_days_max} days";
        }
        if ($this->estimated_days_min) {
            return "{$this->estimated_days_min}+ days";
        }
        return '';
    }

    public function supportsCountry(string $countryCode): bool
    {
        if (empty($this->supported_countries)) {
            return true; // null = supports all
        }
        return in_array(strtoupper($countryCode), $this->supported_countries, true);
    }
}

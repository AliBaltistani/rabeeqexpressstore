<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShippingCarrier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'logo',
        'tracking_url_template',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    // ─── Scopes ───

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // ─── Helpers ───

    /**
     * Generate a tracking URL for the given tracking number.
     */
    public function getTrackingUrl(string $trackingNumber): ?string
    {
        if (empty($this->tracking_url_template)) {
            return null;
        }

        return str_replace('{tracking_number}', urlencode($trackingNumber), $this->tracking_url_template);
    }
}

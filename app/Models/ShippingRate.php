<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;
use App\Models\Model;


class ShippingRate extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'zone_id',
        'name',
        'method',
        'price',
        'min_order_for_free',
        'min_weight',
        'max_weight',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'min_order_for_free' => 'decimal:2',
        'min_weight' => 'decimal:2',
        'max_weight' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }
}

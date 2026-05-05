<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

use App\Models\Model;

class FlashSale extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'name',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(FlashSaleProduct::class);
    }
}

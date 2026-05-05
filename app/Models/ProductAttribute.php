<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;
use App\Models\Model;

class ProductAttribute extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'name',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'attribute_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;
use App\Models\Model;

class ProductAttributeValue extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['value'];

    protected $fillable = [
        'attribute_id',
        'value',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'attribute_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_attribute_value_product',
            'attribute_value_id',
            'product_id'
        );
    }
}

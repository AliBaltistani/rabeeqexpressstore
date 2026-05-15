<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;
use App\Models\Model;

class Slider extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['title'];

    protected $fillable = [
        'title',
        'image',
        'link_url',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope: active sliders ordered by sort.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}

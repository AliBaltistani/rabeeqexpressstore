<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;
use App\Models\Model;

class Banner extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['title', 'subtitle'];

    protected $fillable = [
        'title',
        'subtitle',
        'image',
        'link_url',
        'position',
        'is_active',
        'sort_order',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
}

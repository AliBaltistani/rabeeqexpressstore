<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;
use App\Models\Model;

class BlogPost extends Model
{
    use HasFactory, SoftDeletes, HasTranslations, HasSlug;

    public array $translatable = ['title', 'excerpt', 'content'];

    protected $fillable = [
        'category_id',
        'admin_id',
        'slug',
        'title',
        'excerpt',
        'content',
        'featured_image',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
        'view_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;
use App\Models\Model;

class CmsPage extends Model
{
    use HasFactory, SoftDeletes, HasTranslations, HasSlug;

    public array $translatable = ['title', 'content', 'excerpt'];

    protected $table = 'cms_pages';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'template',
        'status',
        'featured_image',
        'custom_css',
        'meta_title',
        'meta_description',
        'sort_order',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }
}

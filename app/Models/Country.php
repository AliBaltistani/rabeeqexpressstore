<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'name_en',
        'name_ar',
        'code',
        'phone_code',
        'flag',
        'is_active',
    ];

    protected $appends = [
        'name',
        'flag_url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getNameAttribute()
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function getFlagUrlAttribute()
    {
        if ($this->flag) {
            return asset('storage/' . $this->flag);
        }
        return null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Model;

class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'direction',
        'flag_icon',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];
}

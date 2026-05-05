<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Model;


class ShippingZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'countries',
    ];

    protected $casts = [
        'countries' => 'json',
    ];

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class, 'zone_id');
    }
}

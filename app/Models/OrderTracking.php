<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Model;


class OrderTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'tracking_number',
        'carrier',
        'tracking_url',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

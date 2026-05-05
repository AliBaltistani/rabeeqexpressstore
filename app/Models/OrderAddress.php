<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\Model;

class OrderAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'type',
        'first_name',
        'last_name',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'country',
        'postal_code',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

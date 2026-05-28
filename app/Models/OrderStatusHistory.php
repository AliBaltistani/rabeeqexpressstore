<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Model;

class OrderStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'order_status_history';

    protected $fillable = [
        'order_id',
        'status',
        'status_from',
        'status_to',
        'comment',
        'is_customer_notified',
        'created_by',
        'changed_by',
    ];

    protected $casts = [
        'is_customer_notified' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function changedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'changed_by');
    }
}

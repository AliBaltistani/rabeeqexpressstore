<?php

namespace App\Models;

use App\Models\Model;

class LoyaltyTransactionExpiration extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'earned_transaction_id',
        'expired_transaction_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function earnedTransaction()
    {
        return $this->belongsTo(LoyaltyTransaction::class, 'earned_transaction_id');
    }

    public function expiredTransaction()
    {
        return $this->belongsTo(LoyaltyTransaction::class, 'expired_transaction_id');
    }
}

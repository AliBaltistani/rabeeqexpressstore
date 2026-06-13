<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Translatable\HasTranslations;
use App\Models\Model;

class LoyaltyTransaction extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['description'];

    protected $fillable = [
        'user_id',
        'type',
        'points',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
        'admin_id',
        'expires_at',
    ];

    protected $casts = [
        'points' => 'integer',
        'balance_after' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if this earned transaction has already been expired.
     */
    public function expiredCounterpart()
    {
        return $this->hasOne(\App\Models\LoyaltyTransactionExpiration::class, 'earned_transaction_id');
    }
}

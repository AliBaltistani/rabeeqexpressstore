<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\UserAddress;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SoftDeletes;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'profile_completed',
        'promotional_messages',
        'email',
        'password',
        'phone',
        'avatar',
        'is_active',
        'is_banned',
        'ban_reason',
        'language_preference',
        'is_rtl',
        'wallet_balance',
        'loyalty_points',
        'social_provider',
        'social_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'is_banned' => 'boolean',
        'is_rtl' => 'boolean',
        'wallet_balance' => 'decimal:2',
        'loyalty_points' => 'integer',
        'birth_date' => 'date',
        'profile_completed' => 'boolean',
        'promotional_messages' => 'boolean',
    ];

    // ── Relationships ──

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function couponUsages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    // ── Wallet Helpers ──

    public function creditWallet(float $amount, array $description = [], ?string $refType = null, ?int $refId = null, ?int $adminId = null): WalletTransaction
    {
        return DB::transaction(function () use ($amount, $description, $refType, $refId, $adminId) {
            $this->increment('wallet_balance', $amount);
            $this->refresh();

            return $this->walletTransactions()->create([
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => $this->wallet_balance,
                'description' => $description,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'admin_id' => $adminId,
            ]);
        });
    }

    public function debitWallet(float $amount, array $description = [], ?string $refType = null, ?int $refId = null, ?int $adminId = null): WalletTransaction
    {
        return DB::transaction(function () use ($amount, $description, $refType, $refId, $adminId) {
            if ($this->wallet_balance < $amount) {
                throw new \RuntimeException('Insufficient wallet balance.');
            }

            $this->decrement('wallet_balance', $amount);
            $this->refresh();

            return $this->walletTransactions()->create([
                'type' => 'debit',
                'amount' => $amount,
                'balance_after' => $this->wallet_balance,
                'description' => $description,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'admin_id' => $adminId,
            ]);
        });
    }

    // ── Loyalty Helpers ──

    public function addLoyaltyPoints(int $points, string $type = 'earned', array $description = [], ?string $refType = null, ?int $refId = null, ?int $adminId = null): LoyaltyTransaction
    {
        return DB::transaction(function () use ($points, $type, $description, $refType, $refId, $adminId) {
            $this->increment('loyalty_points', $points);
            $this->refresh();

            return $this->loyaltyTransactions()->create([
                'type' => $type,
                'points' => $points,
                'balance_after' => $this->loyalty_points,
                'description' => $description,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'admin_id' => $adminId,
            ]);
        });
    }

    public function redeemLoyaltyPoints(int $points, array $description = [], ?string $refType = null, ?int $refId = null): LoyaltyTransaction
    {
        return DB::transaction(function () use ($points, $description, $refType, $refId) {
            if ($this->loyalty_points < $points) {
                throw new \RuntimeException('Insufficient loyalty points.');
            }

            $this->decrement('loyalty_points', $points);
            $this->refresh();

            return $this->loyaltyTransactions()->create([
                'type' => 'redeemed',
                'points' => $points,
                'balance_after' => $this->loyalty_points,
                'description' => $description,
                'reference_type' => $refType,
                'reference_id' => $refId,
            ]);
        });
    }

    // ── Misc ──

    public function sendPasswordResetNotification($token)
    {
        if (setting('email.notify_password_reset', true)) {
            parent::sendPasswordResetNotification($token);
        }
    }
}

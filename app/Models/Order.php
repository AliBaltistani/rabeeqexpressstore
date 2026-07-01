<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Model;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'guest_email',
        'guest_name',
        'guest_phone',
        'status',
        'payment_status',
        'payment_method',
        'payment_gateway',
        'payment_intent_id',
        'gateway_order_id',
        'gateway_status',
        'gateway_payload',
        'shipping_rate_id',
        'shipping_method',
        'shipping_status',
        'transaction_id',
        'tracking_number',
        'subtotal',
        'discount_amount',
        'shipping_amount',
        'free_shipping_applied',
        'tax_amount',
        'total',
        'currency_code',
        'currency_rate',
        'coupon_id',
        'coupon_code',
        'loyalty_points_earned',
        'loyalty_points_redeemed',
        'notes',
        'ip_address',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'currency_rate' => 'decimal:6',
        'free_shipping_applied' => 'boolean',
        'gateway_payload' => 'json',
        'loyalty_points_earned' => 'integer',
        'loyalty_points_redeemed' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(OrderAddress::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function tracking(): HasOne
    {
        return $this->hasOne(OrderTracking::class);
    }

    public function billingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class)->where('type', 'billing');
    }

    public function shippingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class)->where('type', 'shipping');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', 'processing');
    }

    public function scopeShipped(Builder $query): Builder
    {
        return $query->where('status', 'shipped');
    }

    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', 'delivered');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            default => ucfirst($this->status),
        };
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            'unpaid' => 'Unpaid',
            'paid' => 'Paid',
            'refunded' => 'Refunded',
            'partially_refunded' => 'Partially Refunded',
            default => ucfirst(str_replace('_', ' ', $this->payment_status)),
        };
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format((float) $this->total, 2) . ' ' . $this->currency_code;
    }

    public function getShippingStatusLabelAttribute(): string
    {
        return match ($this->shipping_status) {
            'pending'    => 'Pending',
            'booked'     => 'Booked',
            'in_transit'  => 'In Transit',
            'delivered'  => 'Delivered',
            default      => ucfirst($this->shipping_status ?? 'pending'),
        };
    }
}

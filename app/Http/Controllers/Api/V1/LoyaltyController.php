<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Coupon;
use App\Models\LoyaltyReward;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoyaltyController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/loyalty
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $locale = app()->getLocale();

        $recentTransactions = $user->loyaltyTransactions()
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($t) => [
                'id'          => $t->id,
                'type'        => $t->type,
                'points'      => $t->points,
                'balanceAfter' => $t->balance_after,
                'description' => $t->getTranslation('description', $locale),
                'createdAt'   => $t->created_at->toISOString(),
                'expiresAt'   => $t->expires_at?->toISOString(),
            ]);

        $redeemRate = (float) Setting::get('loyalty.redeem_rate', 100);
        $minRedeem  = (int) Setting::get('loyalty.min_redeem', 100);
        $earnRate   = (float) Setting::get('loyalty.earn_rate', 1);
        $profileCompletionPoints = (int) Setting::get('loyalty.profile_completion_points', 0);
        $pointsExpiryDays = (int) Setting::get('loyalty.points_expiry_days', 0);

        return $this->success([
            'points'                   => $user->loyalty_points,
            'redeemRate'               => $redeemRate,
            'minRedeem'                => $minRedeem,
            'walletValuePerPoint'      => $redeemRate > 0 ? round(1 / $redeemRate, 4) : 0,
            'earnRate'                 => $earnRate,
            'profileCompletionPoints'  => $profileCompletionPoints,
            'sharePoints'              => (int) Setting::get('loyalty.share_points', 50),
            'storeUrl'                 => Setting::get('loyalty.store_url') ?: config('app.frontend_url', config('app.url', 'https://eseven-store.com')),
            'profileCompleted'         => (bool) $user->profile_completed,
            'pointsExpiryDays'         => $pointsExpiryDays,
            'recentTransactions'       => $recentTransactions,
        ]);
    }

    /**
     * GET /api/v1/loyalty/rewards
     */
    public function rewards(Request $request): JsonResponse
    {
        $locale = app()->getLocale();

        $rewards = LoyaltyReward::active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn($r) => [
                'id'                          => $r->id,
                'slug'                        => $r->slug,
                'type'                        => $r->type,
                'name'                        => $r->getTranslation('name', $locale),
                'description'                 => $r->getTranslation('description', $locale),
                'pointsCost'                  => $r->points_cost,
                'discountValue'               => $r->discount_value,
                'discountType'                => $r->discount_type,
                'image'                       => $r->image,
                'applicableShippingMethodIds' => $r->applicable_shipping_method_ids,
            ]);

        return $this->success([
            'discounts'    => $rewards->where('type', 'discount')->values(),
            'freeShipping' => $rewards->where('type', 'free_shipping')->values(),
        ]);
    }

    /**
     * GET /api/v1/loyalty/transactions
     */
    public function transactions(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('perPage', 15), 50);
        $locale = app()->getLocale();

        $transactions = $request->user()
            ->loyaltyTransactions()
            ->latest()
            ->paginate($perPage);

        $data = $transactions->through(fn($t) => [
            'id'          => $t->id,
            'type'        => $t->type,
            'points'      => $t->points,
            'balanceAfter' => $t->balance_after,
            'description' => $t->getTranslation('description', $locale),
            'createdAt'   => $t->created_at->toISOString(),
            'expiresAt'   => $t->expires_at?->toISOString(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $data->items(),
            'meta'    => [
                'total'   => $data->total(),
                'page'    => $data->currentPage(),
                'perPage' => $data->perPage(),
                'lastPage' => $data->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/loyalty/redeem-to-wallet
     * Convert loyalty points to wallet balance.
     */
    public function redeemToWallet(Request $request): JsonResponse
    {
        $request->validate([
            'points' => ['required', 'integer', 'min:1'],
        ]);

        $user = $request->user();
        $points = (int) $request->input('points');

        // Check loyalty is enabled
        if (!setting('loyalty.enabled', false)) {
            return $this->error('Loyalty program is currently disabled.', 422);
        }

        // Check minimum redeem threshold
        $minRedeem = (int) Setting::get('loyalty.min_redeem', 100);
        if ($points < $minRedeem) {
            return $this->error("Minimum {$minRedeem} points required to redeem.", 422);
        }

        // Check user has enough points
        if ($user->loyalty_points < $points) {
            return $this->error('Insufficient loyalty points. You have ' . $user->loyalty_points . ' points.', 422);
        }

        // Calculate wallet credit amount
        $redeemRate = (float) Setting::get('loyalty.redeem_rate', 100);
        $walletAmount = round($points / $redeemRate, 2);

        if ($walletAmount <= 0) {
            return $this->error('Points amount too small to convert.', 422);
        }

        try {
            // Deduct loyalty points
            $user->redeemLoyaltyPoints(
                $points,
                [
                    'en' => "Redeemed {$points} points to wallet",
                    'ar' => "استبدال {$points} نقطة إلى المحفظة",
                ],
            );

            // Credit wallet
            $user->creditWallet(
                $walletAmount,
                [
                    'en' => "Loyalty points redemption ({$points} pts)",
                    'ar' => "استبدال نقاط الولاء ({$points} نقطة)",
                ],
            );

            $user->refresh();

            return $this->success([
                'pointsRedeemed'  => $points,
                'walletCredited'  => $walletAmount,
                'loyaltyBalance'  => $user->loyalty_points,
                'walletBalance'   => round((float) $user->wallet_balance, 2),
            ], "Successfully redeemed {$points} points for " . currency_symbol() . " {$walletAmount}.");

        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * POST /api/v1/loyalty/redeem-reward
     * Redeem a loyalty reward → generates a user-bound coupon.
     */
    public function redeemReward(Request $request): JsonResponse
    {
        $request->validate([
            'rewardId' => ['required', 'integer'],
        ]);

        $user = $request->user();

        // Check loyalty is enabled
        if (!setting('loyalty.enabled', false)) {
            return $this->error('Loyalty program is currently disabled.', 422);
        }

        // Load reward
        $reward = LoyaltyReward::active()->find($request->input('rewardId'));
        if (!$reward) {
            return $this->error('Reward not found or no longer available.', 404);
        }

        // Check user has enough points
        if ($user->loyalty_points < $reward->points_cost) {
            return $this->error(
                'Insufficient points. You need ' . $reward->points_cost . ' points but have ' . $user->loyalty_points . '.',
                422
            );
        }

        $locale = app()->getLocale();

        try {
            return DB::transaction(function () use ($user, $reward, $locale) {
                // Generate unique coupon code
                $code = 'LYL-' . $user->id . '-' . strtoupper(Str::random(6));

                // Determine coupon type and value from reward
                $couponType = match ($reward->type) {
                    'discount' => $reward->discount_type === 'fixed' ? 'fixed' : 'percentage',
                    'free_shipping' => 'free_shipping',
                    default => 'percentage',
                };

                $couponValue = match ($reward->type) {
                    'discount' => (float) ($reward->discount_value ?? 0),
                    'free_shipping' => 0,
                    default => 0,
                };

                // Calculate expiry
                $expiryDays = (int) Setting::get('loyalty.coupon_expiry_days', 30);
                $expiresAt = now()->addDays($expiryDays);

                // Create user-bound coupon
                $coupon = Coupon::create([
                    'user_id'                       => $user->id,
                    'loyalty_reward_id'             => $reward->id,
                    'code'                          => $code,
                    'name'                          => $reward->getTranslation('name', 'en'),
                    'description'                   => $reward->getTranslation('description', 'en'),
                    'type'                          => $couponType,
                    'value'                         => $couponValue,
                    'max_discount_amount'           => null,
                    'min_order_amount'              => 0,
                    'usage_limit'                   => 1,
                    'usage_limit_per_user'          => 1,
                    'usage_count'                   => 0,
                    'applies_to'                    => 'all',
                    'exclude_sale_items'            => false,
                    'is_active'                     => true,
                    'source'                        => 'loyalty',
                    'applicable_shipping_method_ids' => $reward->applicable_shipping_method_ids,
                    'starts_at'                     => now(),
                    'expires_at'                    => $expiresAt,
                ]);

                // Deduct loyalty points
                $user->redeemLoyaltyPoints(
                    $reward->points_cost,
                    [
                        'en' => "Redeemed for: {$reward->getTranslation('name', 'en')}",
                        'ar' => "تم الاستبدال: {$reward->getTranslation('name', 'ar')}",
                    ],
                    LoyaltyReward::class,
                    $reward->id,
                );

                $user->refresh();

                return $this->success([
                    'couponCode'     => $coupon->code,
                    'couponType'     => $coupon->type,
                    'couponValue'    => $coupon->value,
                    'expiresAt'      => $coupon->expires_at->toISOString(),
                    'rewardName'     => $reward->getTranslation('name', $locale),
                    'pointsSpent'    => $reward->points_cost,
                    'loyaltyBalance' => $user->loyalty_points,
                ], 'Reward redeemed successfully! Your coupon code is: ' . $coupon->code);
            });
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * GET /api/v1/loyalty/my-coupons
     * Returns all coupons generated from loyalty rewards for the current user.
     */
    public function myCoupons(Request $request): JsonResponse
    {
        $user = $request->user();
        $locale = app()->getLocale();

        $coupons = Coupon::ownedBy($user->id)
            ->fromSource('loyalty')
            ->with('loyaltyReward')
            ->latest()
            ->get()
            ->map(function (Coupon $coupon) use ($locale) {
                // Determine status
                $status = 'active';
                if ($coupon->usage_count >= ($coupon->usage_limit ?? PHP_INT_MAX)) {
                    $status = 'used';
                } elseif ($coupon->expires_at && $coupon->expires_at->isPast()) {
                    $status = 'expired';
                } elseif (!$coupon->is_active) {
                    $status = 'inactive';
                }

                return [
                    'id'            => $coupon->id,
                    'code'          => $coupon->code,
                    'type'          => $coupon->type,
                    'value'         => (float) $coupon->value,
                    'name'          => $coupon->name,
                    'description'   => $coupon->description,
                    'status'        => $status,
                    'expiresAt'     => $coupon->expires_at?->toISOString(),
                    'createdAt'     => $coupon->created_at->toISOString(),
                    'rewardName'    => $coupon->loyaltyReward
                        ? $coupon->loyaltyReward->getTranslation('name', $locale)
                        : $coupon->name,
                    'rewardType'    => $coupon->loyaltyReward?->type ?? $coupon->type,
                    'applicableShippingMethodIds' => $coupon->applicable_shipping_method_ids,
                ];
            });

        // Group by status for frontend convenience
        return $this->success([
            'coupons'  => $coupons,
            'active'   => $coupons->where('status', 'active')->values(),
            'used'     => $coupons->where('status', 'used')->values(),
            'expired'  => $coupons->where('status', 'expired')->values(),
        ]);
    }
}

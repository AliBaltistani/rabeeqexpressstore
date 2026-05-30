<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            ]);

        $redeemRate = (float) Setting::get('loyalty.redeem_rate', 100);
        $minRedeem  = (int) Setting::get('loyalty.min_redeem', 100);

        return $this->success([
            'points'              => $user->loyalty_points,
            'redeemRate'          => $redeemRate,
            'minRedeem'           => $minRedeem,
            'walletValuePerPoint' => $redeemRate > 0 ? round(1 / $redeemRate, 4) : 0,
            'recentTransactions'  => $recentTransactions,
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
}

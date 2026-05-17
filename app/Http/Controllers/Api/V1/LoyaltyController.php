<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
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
                'id' => $t->id,
                'type' => $t->type,
                'points' => $t->points,
                'balanceAfter' => $t->balance_after,
                'description' => $t->getTranslation('description', $locale),
                'createdAt' => $t->created_at->toISOString(),
            ]);

        return $this->success([
            'points' => $user->loyalty_points,
            'recentTransactions' => $recentTransactions,
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
            'id' => $t->id,
            'type' => $t->type,
            'points' => $t->points,
            'balanceAfter' => $t->balance_after,
            'description' => $t->getTranslation('description', $locale),
            'createdAt' => $t->created_at->toISOString(),
        ]);

        return $this->paginated($data);
    }
}

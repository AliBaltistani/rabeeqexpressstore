<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/wallet
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $locale = app()->getLocale();

        $recentTransactions = $user->walletTransactions()
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'amount' => round((float) $t->amount, 2),
                'balanceAfter' => round((float) $t->balance_after, 2),
                'description' => $t->getTranslation('description', $locale),
                'createdAt' => $t->created_at->toISOString(),
            ]);

        return $this->success([
            'balance' => round((float) $user->wallet_balance, 2),
            'currency' => currency_code(),
            'recentTransactions' => $recentTransactions,
        ]);
    }

    /**
     * GET /api/v1/wallet/transactions
     */
    public function transactions(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('perPage', 15), 50);
        $locale = app()->getLocale();

        $transactions = $request->user()
            ->walletTransactions()
            ->latest()
            ->paginate($perPage);

        $data = $transactions->through(fn($t) => [
            'id' => $t->id,
            'type' => $t->type,
            'amount' => round((float) $t->amount, 2),
            'balanceAfter' => round((float) $t->balance_after, 2),
            'description' => $t->getTranslation('description', $locale),
            'createdAt' => $t->created_at->toISOString(),
        ]);

        return $this->paginated($data);
    }
}

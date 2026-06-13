<?php

namespace App\Console\Commands;

use App\Models\LoyaltyTransaction;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireLoyaltyPointsCommand extends Command
{
    protected $signature = 'loyalty:expire-points';
    protected $description = 'Expire loyalty points that have passed their expiry date';

    public function handle(): int
    {
        $expiryDays = (int) Setting::get('loyalty.points_expiry_days', 0);

        if ($expiryDays <= 0) {
            $this->info('Points expiry is disabled (loyalty.points_expiry_days = 0).');
            return self::SUCCESS;
        }

        $this->info("Processing expired loyalty points (expiry: {$expiryDays} days)...");

        // Find all earned transactions that have expired but haven't been processed
        $expiredTransactions = LoyaltyTransaction::where('type', 'earned')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereDoesntHave('expiredCounterpart')
            ->with('user')
            ->get();

        if ($expiredTransactions->isEmpty()) {
            $this->info('No expired points found.');
            return self::SUCCESS;
        }

        // Group by user
        $grouped = $expiredTransactions->groupBy('user_id');
        $totalUsers = 0;
        $totalPoints = 0;

        foreach ($grouped as $userId => $transactions) {
            $user = User::find($userId);
            if (!$user) {
                continue;
            }

            $pointsToExpire = $transactions->sum('points');

            // Don't expire more than user has
            $pointsToExpire = min($pointsToExpire, $user->loyalty_points);

            if ($pointsToExpire <= 0) {
                continue;
            }

            DB::transaction(function () use ($user, $pointsToExpire, $transactions) {
                // Deduct expired points
                $user->decrement('loyalty_points', $pointsToExpire);
                $user->refresh();

                // Create expired transaction
                $expiredTx = $user->loyaltyTransactions()->create([
                    'type'        => 'expired',
                    'points'      => -$pointsToExpire,
                    'balance_after' => $user->loyalty_points,
                    'description' => [
                        'en' => "{$pointsToExpire} points expired",
                        'ar' => "انتهت صلاحية {$pointsToExpire} نقطة",
                    ],
                    'reference_type' => LoyaltyTransaction::class,
                    'reference_id'   => $transactions->first()->id,
                ]);

                // Mark original transactions as processed by creating a link
                foreach ($transactions as $tx) {
                    DB::table('loyalty_transaction_expirations')->insert([
                        'earned_transaction_id'  => $tx->id,
                        'expired_transaction_id' => $expiredTx->id,
                        'created_at'             => now(),
                    ]);
                }
            });

            $totalUsers++;
            $totalPoints += $pointsToExpire;

            $this->line("  User #{$userId}: expired {$pointsToExpire} points");
        }

        $this->info("Done. Expired {$totalPoints} points for {$totalUsers} users.");
        return self::SUCCESS;
    }
}

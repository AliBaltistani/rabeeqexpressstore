<?php

namespace App\Filament\Resources\CouponResource\Widgets;

use App\Models\Coupon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CouponStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalCoupons = Coupon::count();

        $activeCoupons = Coupon::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();

        $expiredCoupons = Coupon::whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->count();

        $totalUses = Coupon::sum('usage_count');

        return [
            Stat::make('Total Coupons', $totalCoupons)
                ->icon('heroicon-o-ticket')
                ->color('primary'),

            Stat::make('Active', $activeCoupons)
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Expired', $expiredCoupons)
                ->icon('heroicon-o-clock')
                ->color('danger'),

            Stat::make('Total Uses', $totalUses)
                ->icon('heroicon-o-cursor-arrow-rays')
                ->color('info'),
        ];
    }
}

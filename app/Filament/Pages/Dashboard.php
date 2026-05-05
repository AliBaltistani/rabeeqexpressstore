<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardStatsWidget;
use App\Filament\Widgets\LowStockWidget;
use App\Filament\Widgets\OrdersStatusChart;
use App\Filament\Widgets\RecentOrdersWidget;
use App\Filament\Widgets\RevenueChart;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Dashboard';

    public function getColumns(): int | array
    {
        return 2;
    }

    public function getWidgets(): array
    {
        return [
            DashboardStatsWidget::class,
            RevenueChart::class,
            OrdersStatusChart::class,
            RecentOrdersWidget::class,
            LowStockWidget::class,
        ];
    }
}

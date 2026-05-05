<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $service = new DashboardService();
        $currencySymbol = $service->getDefaultCurrencySymbol();

        return [
            Stat::make("Today's Revenue", $currencySymbol . ' ' . number_format($service->getTodayRevenue(), 2))
                ->description('Total paid orders today')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('info')
                ->chart([0, 0, 0, 0, 0, 0, 0]),

            Stat::make('New Orders Today', $service->getNewOrdersToday())
                ->description('Orders placed today')
                ->descriptionIcon('heroicon-o-shopping-bag')
                ->color('success')
                ->chart([0, 0, 0, 0, 0, 0, 0]),

            Stat::make('New Customers Today', $service->getNewCustomersToday())
                ->description('Customers registered today')
                ->descriptionIcon('heroicon-o-user-plus')
                ->color('info')
                ->extraAttributes(['class' => 'text-teal-500']),

            Stat::make('Low Stock Items', $service->getLowStockCount())
                ->description('Products below threshold')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}

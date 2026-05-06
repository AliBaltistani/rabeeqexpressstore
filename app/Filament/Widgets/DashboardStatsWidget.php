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
        $storeName = setting('general.store_name_en', 'Store');

        return [
            Stat::make(__('admin.dashboard.today_revenue'), $currencySymbol . ' ' . number_format($service->getTodayRevenue(), 2))
                ->description(__('admin.dashboard.total_paid_today'))
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('info')
                ->chart([0, 0, 0, 0, 0, 0, 0]),

            Stat::make(__('admin.dashboard.new_orders_today'), $service->getNewOrdersToday())
                ->description(__('admin.dashboard.orders_placed_today'))
                ->descriptionIcon('heroicon-o-shopping-bag')
                ->color('success')
                ->chart([0, 0, 0, 0, 0, 0, 0]),

            Stat::make(__('admin.dashboard.new_customers_today'), $service->getNewCustomersToday())
                ->description(__('admin.dashboard.customers_registered_today'))
                ->descriptionIcon('heroicon-o-user-plus')
                ->color('info')
                ->extraAttributes(['class' => 'text-teal-500']),

            Stat::make(__('admin.dashboard.low_stock_items'), $service->getLowStockCount())
                ->description(__('admin.dashboard.products_below_threshold') . ' (' . (int) setting('general.low_stock_threshold', 5) . ')')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}

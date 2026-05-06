<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\Currency;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    protected const CACHE_TTL = 300; // 5 minutes

    /**
     * Get today's revenue from paid orders.
     */
    public function getTodayRevenue(): float
    {
        return Cache::remember('dashboard:today_revenue:' . now()->toDateString(), self::CACHE_TTL, function () {
            return (float) Order::whereDate('created_at', Carbon::today())
                ->where('payment_status', 'paid')
                ->sum('total');
        });
    }

    /**
     * Get the default currency symbol for display.
     */
    public function getDefaultCurrencySymbol(): string
    {
        return Cache::remember('dashboard:default_currency_symbol', self::CACHE_TTL, function () {
            $currency = Currency::getDefault();
            if ($currency) {
                return $currency->symbol;
            }

            // Fallback to admin settings currency code
            return Setting::get('general.default_currency', 'SAR');
        });
    }

    /**
     * Get count of new orders placed today.
     */
    public function getNewOrdersToday(): int
    {
        return Cache::remember('dashboard:new_orders_today:' . now()->toDateString(), self::CACHE_TTL, function () {
            return Order::whereDate('created_at', Carbon::today())->count();
        });
    }

    /**
     * Get count of new customers registered today.
     */
    public function getNewCustomersToday(): int
    {
        return Cache::remember('dashboard:new_customers_today:' . now()->toDateString(), self::CACHE_TTL, function () {
            return User::whereDate('created_at', Carbon::today())->count();
        });
    }

    /**
     * Get count of products with stock at or below low stock threshold.
     *
     * Uses the product-level threshold first. If a product doesn't have
     * its own threshold, falls back to the global admin setting.
     */
    public function getLowStockCount(): int
    {
        return Cache::remember('dashboard:low_stock_count', self::CACHE_TTL, function () {
            $globalThreshold = (int) Setting::get('general.low_stock_threshold', 5);

            return Product::withoutGlobalScope('active')
                ->where('track_stock', true)
                ->where(function ($query) use ($globalThreshold) {
                    // Products with their own threshold
                    $query->where(function ($q) {
                        $q->whereNotNull('low_stock_threshold')
                            ->where('low_stock_threshold', '>', 0)
                            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
                    })
                    // Products relying on global threshold
                    ->orWhere(function ($q) use ($globalThreshold) {
                        $q->where(function ($inner) {
                            $inner->whereNull('low_stock_threshold')
                                ->orWhere('low_stock_threshold', 0);
                        })
                        ->where('stock_quantity', '<=', $globalThreshold);
                    });
                })
                ->count();
        });
    }

    /**
     * Get revenue chart data for the last 30 days.
     */
    public function getRevenueChartData(): array
    {
        return Cache::remember('dashboard:revenue_chart:' . now()->toDateString(), self::CACHE_TTL, function () {
            $startDate = Carbon::today()->subDays(29);
            $endDate = Carbon::today();

            // Fetch existing data from DB
            $revenueData = Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(total) as revenue')
                )
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->pluck('revenue', 'date')
                ->toArray();

            // Determine the date format from settings
            $dateFormat = admin_date_format();
            // For chart labels, keep it short
            $chartDateFormat = 'M d';

            // Build complete 30-day array with zero-fill for missing dates
            $dates = [];
            $revenue = [];
            $current = $startDate->copy();

            while ($current->lte($endDate)) {
                $dateStr = $current->toDateString();
                $dates[] = $current->format($chartDateFormat);
                $revenue[] = (float) ($revenueData[$dateStr] ?? 0);
                $current->addDay();
            }

            return [
                'dates' => $dates,
                'revenue' => $revenue,
            ];
        });
    }

    /**
     * Get order counts grouped by status.
     */
    public function getOrderStatusChart(): array
    {
        return Cache::remember('dashboard:order_status_chart', self::CACHE_TTL, function () {
            $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
            $result = [];

            $counts = Order::select('status', DB::raw('COUNT(*) as count'))
                ->whereIn('status', $statuses)
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            foreach ($statuses as $status) {
                $result[$status] = $counts[$status] ?? 0;
            }

            return $result;
        });
    }

    /**
     * Get the most recent orders.
     */
    public function getRecentOrders(int $limit = 10)
    {
        return Order::with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get products with lowest stock levels.
     */
    public function getLowStockProducts(int $limit = 10)
    {
        $globalThreshold = (int) Setting::get('general.low_stock_threshold', 5);

        return Product::withoutGlobalScope('active')
            ->where('track_stock', true)
            ->where(function ($query) use ($globalThreshold) {
                $query->where(function ($q) {
                    $q->whereNotNull('low_stock_threshold')
                        ->where('low_stock_threshold', '>', 0)
                        ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
                })
                ->orWhere(function ($q) use ($globalThreshold) {
                    $q->where(function ($inner) {
                        $inner->whereNull('low_stock_threshold')
                            ->orWhere('low_stock_threshold', 0);
                    })
                    ->where('stock_quantity', '<=', $globalThreshold);
                });
            })
            ->orderBy('stock_quantity', 'asc')
            ->limit($limit)
            ->get();
    }
}

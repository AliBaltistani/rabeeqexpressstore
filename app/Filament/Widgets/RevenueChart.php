<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Revenue — Last 30 Days';
    }

    public function getDescription(): ?string
    {
        return 'Daily revenue from paid orders over the last 30 days';
    }

    protected function getMaxHeight(): ?string
    {
        return '300px';
    }

    protected function getData(): array
    {
        $dashboardService = new DashboardService();
        $data = $dashboardService->getRevenueChartData();

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $data['revenue'],
                    'fill' => true,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                    'pointRadius' => 3,
                    'pointBackgroundColor' => 'rgb(59, 130, 246)',
                    'pointHoverRadius' => 5,
                ],
            ],
            'labels' => $data['dates'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
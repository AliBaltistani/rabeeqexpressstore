<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\ChartWidget;

class OrdersStatusChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Orders by Status';
    }

    public function getDescription(): ?string
    {
        return 'Distribution of all orders across statuses';
    }

    protected function getMaxHeight(): ?string
    {
        return '300px';
    }

    protected function getData(): array
    {
        $dashboardService = new DashboardService();
        $data = $dashboardService->getOrderStatusChart();

        return [
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => [
                        $data['pending'],
                        $data['processing'],
                        $data['shipped'],
                        $data['delivered'],
                        $data['cancelled'],
                    ],
                    'backgroundColor' => [
                        'rgba(251, 191, 36, 0.8)',   // Pending — yellow
                        'rgba(59, 130, 246, 0.8)',    // Processing — blue
                        'rgba(20, 184, 166, 0.8)',    // Shipped — teal
                        'rgba(34, 197, 94, 0.8)',     // Delivered — green
                        'rgba(239, 68, 68, 0.8)',     // Cancelled — red
                    ],
                    'borderColor' => [
                        'rgb(251, 191, 36)',
                        'rgb(59, 130, 246)',
                        'rgb(20, 184, 166)',
                        'rgb(34, 197, 94)',
                        'rgb(239, 68, 68)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
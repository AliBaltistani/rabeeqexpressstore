<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Setting;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 1;

    public function getTableHeading(): string
    {
        $threshold = (int) setting('general.low_stock_threshold', 5);
        return __('admin.dashboard.low_stock_alert') . " (≤ {$threshold})";
    }

    public function table(Table $table): Table
    {
        $globalThreshold = (int) setting('general.low_stock_threshold', 5);

        return $table
            ->query(
                Product::withoutGlobalScope('active')
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
                    ->orderBy('stock_quantity', 'asc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Product'))
                    ->searchable()
                    ->limit(40)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('sku')
                    ->label(__('SKU'))
                    ->searchable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label(__('Stock'))
                    ->alignCenter()
                    ->color('danger')
                    ->weight('bold')
                    ->badge(),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('All products are well stocked!'))
            ->emptyStateDescription(__('No products are currently below their low stock threshold.'))
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}

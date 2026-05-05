<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 1;

    public function getTableHeading(): string
    {
        return 'Low Stock Alert';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::withoutGlobalScope('active')
                    ->where('track_stock', true)
                    ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                    ->orderBy('stock_quantity', 'asc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->limit(40)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->alignCenter()
                    ->color('danger')
                    ->weight('bold')
                    ->badge(),
            ])
            ->paginated(false)
            ->emptyStateHeading('All products are well stocked!')
            ->emptyStateDescription('No products are currently below their low stock threshold.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}

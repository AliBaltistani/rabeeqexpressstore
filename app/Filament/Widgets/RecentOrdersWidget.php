<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentOrdersWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    public function getTableHeading(): string
    {
        return 'Recent Orders';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->with('user')
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->state(function (Order $record): string {
                        return $record->user?->name ?? $record->guest_name ?? 'Guest';
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'shipped' => 'gray',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        'refunded' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'unpaid' => 'danger',
                        'refunded' => 'warning',
                        'partially_refunded' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money(fn (Order $record): string => strtolower($record->currency_code ?? 'sar'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false)
            ->emptyStateHeading('No orders yet')
            ->emptyStateDescription('Orders will appear here once customers start placing them.')
            ->emptyStateIcon('heroicon-o-shopping-bag');
    }
}

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
        return __('admin.dashboard.recent_orders');
    }

    public function table(Table $table): Table
    {
        $dateFormat = admin_date_format(withTime: true);
        $defaultCurrency = currency_symbol();

        return $table
            ->query(
                Order::query()
                    ->with('user')
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label(__('Order #'))
                    ->searchable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label(__('Customer'))
                    ->state(function (Order $record): string {
                        return $record->user?->name ?? $record->guest_name ?? __('Guest');
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
                    ->formatStateUsing(fn (string $state): string => __(ucfirst($state))),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label(__('Payment'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'unpaid' => 'danger',
                        'refunded' => 'warning',
                        'partially_refunded' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => __(ucfirst(str_replace('_', ' ', $state)))),

                Tables\Columns\TextColumn::make('total')
                    ->label(__('Total'))
                    ->formatStateUsing(function (Order $record) use ($defaultCurrency): string {
                        $symbol = $record->currency_code ?? $defaultCurrency;
                        return number_format($record->total, 2) . ' ' . $symbol;
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->dateTime($dateFormat)
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false)
            ->emptyStateHeading(__('No orders yet'))
            ->emptyStateDescription(__('Orders will appear here once customers start placing them.'))
            ->emptyStateIcon('heroicon-o-shopping-bag');
    }
}

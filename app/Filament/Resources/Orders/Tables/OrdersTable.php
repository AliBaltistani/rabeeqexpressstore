<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Filament\Tables\Table;
use App\Models\Order;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\BulkAction;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order#')
                    ->searchable()
                    ->sortable()
                    ->url(fn(Order $record): string => \App\Filament\Resources\Orders\OrderResource::getUrl('view', ['record' => $record])),
                TextColumn::make('customer')
                    ->label('Customer')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('user', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })->orWhere('guest_name', 'like', "%{$search}%")
                            ->orWhere('guest_email', 'like', "%{$search}%");
                    })
                    ->getStateUsing(fn(Order $record) => $record->user ? $record->user->name : ($record->guest_name ?? 'Guest'))
                    ->description(fn(Order $record) => $record->user ? $record->user->email : $record->guest_email),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        'refunded' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(Order $record) => $record->status_label),
                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'unpaid' => 'danger',
                        'paid' => 'success',
                        'refunded' => 'warning',
                        'partially_refunded' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(Order $record) => $record->payment_status_label),
                TextColumn::make('payment_method')
                    ->badge()
                    ->icon(fn(string $state): string => match ($state) {
                        'stripe' => 'heroicon-o-credit-card',
                        'paypal' => 'heroicon-o-currency-dollar',
                        'cod' => 'heroicon-o-banknotes',
                        'bank_transfer' => 'heroicon-o-building-library',
                        default => 'heroicon-o-banknotes',
                    }),
                TextColumn::make('total')
                    ->formatStateUsing(fn(Order $record) => $record->formatted_total)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ]),
                SelectFilter::make('payment_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid',
                        'refunded' => 'Refunded',
                        'partially_refunded' => 'Partially Refunded',
                    ]),
                SelectFilter::make('payment_method')
                    ->options([
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'cod' => 'Cash on Delivery',
                        'bank_transfer' => 'Bank Transfer',
                    ]),
                SelectFilter::make('currency_code')
                    ->label('Currency')
                    ->options(function () {
                        return \App\Models\Currency::where('is_active', true)->pluck('code', 'code')->toArray();
                    }),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('Date from'),
                        DatePicker::make('created_until')->label('Date until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make('table')->fromTable()
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markAsProcessing')
                        ->label('Mark as Processing')
                        ->icon('heroicon-o-arrow-path')
                        ->action(fn(\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['status' => 'processing'])),
                    BulkAction::make('markAsShipped')
                        ->label('Mark as Shipped')
                        ->icon('heroicon-o-truck')
                        ->action(fn(\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['status' => 'shipped'])),
                    BulkAction::make('markAsDelivered')
                        ->label('Mark as Delivered')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn(\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['status' => 'delivered'])),
                    BulkAction::make('markAsCancelled')
                        ->label('Mark as Cancelled')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn(\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['status' => 'cancelled'])),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Table;
use Filament\Tables\Actions\BulkAction;
use App\Models\User;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->circular()
                    ->defaultImageUrl(fn(User $record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name)),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('orders_count')
                    ->counts('orders')
                    ->sortable()
                    ->url(fn(User $record) => \App\Filament\Resources\Orders\OrderResource::getUrl('index', ['tableFilters' => ['user_id' => ['value' => $record->id]]])),
                TextColumn::make('orders_sum_total')
                    ->sum('orders', 'total')
                    ->money('SAR')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->getStateUsing(fn(User $record) => $record->is_banned ? 'Banned' : ($record->is_active ? 'Active' : 'Inactive'))
                    ->color(fn($state) => match ($state) {
                        'Banned' => 'danger',
                        'Active' => 'success',
                        'Inactive' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Joined Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
                Filter::make('status')
                    ->form([
                        \Filament\Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'banned' => 'Banned',
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['status'] === 'active', fn($q) => $q->where('is_active', true)->where('is_banned', false))
                            ->when($data['status'] === 'banned', fn($q) => $q->where('is_banned', true));
                    }),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('Joined from'),
                        DatePicker::make('created_until')->label('Joined until'),
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
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('ban')
                        ->label('Ban')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn(\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['is_banned' => true, 'is_active' => false])),
                    BulkAction::make('unban')
                        ->label('Unban')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn(\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['is_banned' => false, 'is_active' => true])),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

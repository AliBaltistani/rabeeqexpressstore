<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\User;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'customers';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.customers');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.customers');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                // Read-only display for view page
                Forms\Components\Placeholder::make('name_display')
                    ->label('Name')
                    ->content(fn(?User $record): string => $record?->name ?? '—'),

                Forms\Components\Placeholder::make('email_display')
                    ->label('Email')
                    ->content(fn(?User $record): string => $record?->email ?? '—'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(fn(User $record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=3b82f6&color=fff'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->icon('heroicon-o-envelope'),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Total Spent')
                    ->getStateUsing(function (User $record): string {
                        $total = $record->orders()->where('payment_status', 'paid')->sum('total');
                        return number_format($total, 2) . ' ' . currency_symbol();
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->withSum(['orders' => fn($q) => $q->where('payment_status', 'paid')], 'total')
                            ->orderBy('orders_sum_total', $direction);
                    }),

                Tables\Columns\TextColumn::make('wallet_balance')
                    ->label(__('admin.wallet.balance'))
                    ->formatStateUsing(fn(User $record) => number_format((float) $record->wallet_balance, 2) . ' ' . currency_symbol())
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('loyalty_points')
                    ->label(__('admin.loyalty.points'))
                    ->sortable()
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(fn(User $record): string => $record->is_banned ? 'Banned' : ($record->is_active ? 'Active' : 'Inactive'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Active' => 'success',
                        'Banned' => 'danger',
                        'Inactive' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime(admin_date_format())
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->label('Active')
                    ->query(fn(Builder $query): Builder => $query->where('is_active', true)->where('is_banned', false))
                    ->toggle(),

                Tables\Filters\Filter::make('banned')
                    ->label('Banned')
                    ->query(fn(Builder $query): Builder => $query->where('is_banned', true))
                    ->toggle(),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('joined_from')
                            ->label('Joined From'),
                        Forms\Components\DatePicker::make('joined_until')
                            ->label('Joined Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['joined_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['joined_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Actions\ViewAction::make(),

                Actions\Action::make('ban')
                    ->label('Ban')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn(User $record): bool => !$record->is_banned)
                    ->form([
                        Forms\Components\Textarea::make('ban_reason')
                            ->label('Ban Reason')
                            ->required()
                            ->rows(3)
                            ->placeholder('Reason for banning this customer...'),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update([
                            'is_banned' => true,
                            'ban_reason' => $data['ban_reason'],
                        ]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Ban Customer')
                    ->modalDescription(fn(User $record): string => "Are you sure you want to ban {$record->name}?"),

                Actions\Action::make('unban')
                    ->label('Unban')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(User $record): bool => $record->is_banned)
                    ->action(function (User $record): void {
                        $record->update([
                            'is_banned' => false,
                            'ban_reason' => null,
                        ]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Unban Customer')
                    ->modalDescription(fn(User $record): string => "Are you sure you want to unban {$record->name}?"),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('ban_selected')
                        ->label('Ban')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->action(fn($records) => $records->each->update(['is_banned' => true]))
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    Actions\BulkAction::make('unban_selected')
                        ->label('Unban')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn($records) => $records->each->update(['is_banned' => false, 'ban_reason' => null]))
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'view' => Pages\ViewCustomer::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('customers.view'));
    }
}

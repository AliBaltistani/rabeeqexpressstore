<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletTransactionResource\Pages;
use App\Models\User;
use App\Models\WalletTransaction;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.wallet_transactions');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('admin.common.customer'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('admin.common.status'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'credit' => 'success',
                        'debit' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label(__('admin.wallet.amount'))
                    ->formatStateUsing(fn(WalletTransaction $record) => number_format((float) $record->amount, 2) . ' ' . currency_symbol())
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance_after')
                    ->label(__('admin.wallet.balance_after'))
                    ->formatStateUsing(fn(WalletTransaction $record) => number_format((float) $record->balance_after, 2) . ' ' . currency_symbol()),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('admin.wallet.description'))
                    ->formatStateUsing(fn(WalletTransaction $record) => $record->getTranslation('description', 'en') ?: '—')
                    ->limit(50),

                Tables\Columns\TextColumn::make('admin.name')
                    ->label(__('admin.wallet.by_admin'))
                    ->placeholder('System'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.common.date'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(['credit' => 'Credit', 'debit' => 'Debit']),
            ])
            ->actions([])
            ->headerActions([
                Actions\Action::make('credit_wallet')
                    ->label(__('admin.wallet.credit'))
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label(__('admin.common.customer'))
                            ->options(User::pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->label(__('admin.wallet.amount'))
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->prefix(currency_symbol()),

                        Forms\Components\TextInput::make('description_en')
                            ->label(__('admin.wallet.description') . ' (EN)')
                            ->required(),

                        Forms\Components\TextInput::make('description_ar')
                            ->label(__('admin.wallet.description') . ' (AR)')
                            ->extraInputAttributes(['dir' => 'rtl']),
                    ])
                    ->action(function (array $data): void {
                        $user = User::findOrFail($data['user_id']);
                        $user->creditWallet(
                            (float) $data['amount'],
                            array_filter(['en' => $data['description_en'], 'ar' => $data['description_ar'] ?? null]),
                            null, null,
                            auth()->guard('admin')->id()
                        );
                        Notification::make()->title('Wallet credited successfully.')->success()->send();
                    }),

                Actions\Action::make('debit_wallet')
                    ->label(__('admin.wallet.debit'))
                    ->icon('heroicon-o-minus-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label(__('admin.common.customer'))
                            ->options(User::pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->label(__('admin.wallet.amount'))
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->prefix(currency_symbol()),

                        Forms\Components\TextInput::make('description_en')
                            ->label(__('admin.wallet.description') . ' (EN)')
                            ->required(),

                        Forms\Components\TextInput::make('description_ar')
                            ->label(__('admin.wallet.description') . ' (AR)')
                            ->extraInputAttributes(['dir' => 'rtl']),
                    ])
                    ->action(function (array $data): void {
                        $user = User::findOrFail($data['user_id']);
                        try {
                            $user->debitWallet(
                                (float) $data['amount'],
                                array_filter(['en' => $data['description_en'], 'ar' => $data['description_ar'] ?? null]),
                                null, null,
                                auth()->guard('admin')->id()
                            );
                            Notification::make()->title('Wallet debited successfully.')->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    })
                    ->requiresConfirmation(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWalletTransactions::route('/'),
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

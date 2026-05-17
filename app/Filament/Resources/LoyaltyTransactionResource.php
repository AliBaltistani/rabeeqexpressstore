<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoyaltyTransactionResource\Pages;
use App\Models\LoyaltyTransaction;
use App\Models\User;
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

class LoyaltyTransactionResource extends Resource
{
    protected static ?string $model = LoyaltyTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.loyalty_transactions');
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
                        'earned' => 'success',
                        'redeemed' => 'warning',
                        'expired' => 'gray',
                        'adjusted' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('points')
                    ->label(__('admin.loyalty.points'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance_after')
                    ->label(__('admin.loyalty.balance_after')),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('admin.loyalty.description'))
                    ->formatStateUsing(fn(LoyaltyTransaction $record) => $record->getTranslation('description', 'en') ?: '—')
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
                    ->options([
                        'earned' => 'Earned',
                        'redeemed' => 'Redeemed',
                        'expired' => 'Expired',
                        'adjusted' => 'Adjusted',
                    ]),
            ])
            ->actions([])
            ->headerActions([
                Actions\Action::make('adjust_points')
                    ->label(__('admin.loyalty.adjust'))
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label(__('admin.common.customer'))
                            ->options(User::pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('points')
                            ->label(__('admin.loyalty.points'))
                            ->numeric()
                            ->required()
                            ->minValue(1),

                        Forms\Components\Select::make('type')
                            ->label('Action')
                            ->options(['earned' => 'Add Points', 'adjusted' => 'Adjust (Add)', 'redeemed' => 'Deduct'])
                            ->default('adjusted')
                            ->required(),

                        Forms\Components\TextInput::make('description_en')
                            ->label(__('admin.loyalty.description') . ' (EN)')
                            ->required(),

                        Forms\Components\TextInput::make('description_ar')
                            ->label(__('admin.loyalty.description') . ' (AR)')
                            ->extraInputAttributes(['dir' => 'rtl']),
                    ])
                    ->action(function (array $data): void {
                        $user = User::findOrFail($data['user_id']);
                        $desc = array_filter(['en' => $data['description_en'], 'ar' => $data['description_ar'] ?? null]);

                        if ($data['type'] === 'redeemed') {
                            try {
                                $user->redeemLoyaltyPoints((int) $data['points'], $desc);
                                Notification::make()->title('Points deducted successfully.')->success()->send();
                            } catch (\RuntimeException $e) {
                                Notification::make()->title($e->getMessage())->danger()->send();
                            }
                        } else {
                            $user->addLoyaltyPoints(
                                (int) $data['points'],
                                $data['type'],
                                $desc,
                                null, null,
                                auth()->guard('admin')->id()
                            );
                            Notification::make()->title('Points added successfully.')->success()->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoyaltyTransactions::route('/'),
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

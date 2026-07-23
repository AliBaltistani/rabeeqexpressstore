<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoyaltyRewardResource\Pages;
use App\Models\LoyaltyReward;
use App\Models\ShippingMethod;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LoyaltyRewardResource extends Resource
{
    protected static ?string $model = LoyaltyReward::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'slug';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.promotions');
    }

    public static function getNavigationLabel(): string
    {
        return 'Loyalty Rewards';
    }

    public static function getModelLabel(): string
    {
        return 'Loyalty Reward';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Loyalty Rewards';
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->columns(1)
            ->schema([
                Schemas\Components\Grid::make(3)
                    ->schema([
                        // ── MAIN CONTENT (left 2/3) ──
                        Schemas\Components\Group::make()
                            ->schema([
                                Schemas\Components\Section::make('Reward Details')
                                    ->icon('heroicon-o-star')
                                    ->schema([
                                        Forms\Components\TextInput::make('slug')
                                            ->label('Slug (Unique ID)')
                                            ->required()
                                            ->unique(LoyaltyReward::class, 'slug', ignoreRecord: true)
                                            ->maxLength(100)
                                            ->helperText('Unique identifier, e.g. "discount-20"')
                                            ->extraInputAttributes(['style' => 'font-family: monospace;']),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('name.en')
                                                    ->label('Name (English)')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('e.g. Coupon discount 20%'),

                                                Forms\Components\TextInput::make('name.ar')
                                                    ->label('Name (Arabic)')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->extraInputAttributes(['dir' => 'rtl'])
                                                    ->placeholder('e.g. كوبون خصم 20%'),
                                            ]),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Textarea::make('description.en')
                                                    ->label('Description (English)')
                                                    ->rows(3)
                                                    ->maxLength(500)
                                                    ->placeholder('Describe the reward in English'),

                                                Forms\Components\Textarea::make('description.ar')
                                                    ->label('Description (Arabic)')
                                                    ->rows(3)
                                                    ->maxLength(500)
                                                    ->extraInputAttributes(['dir' => 'rtl'])
                                                    ->placeholder('وصف المكافأة بالعربية'),
                                            ]),
                                    ]),

                                Schemas\Components\Section::make('Reward Configuration')
                                    ->icon('heroicon-o-cog-6-tooth')
                                    ->schema([
                                        Schemas\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Select::make('type')
                                                    ->label('Reward Type')
                                                    ->options([
                                                        'discount'      => '🏷️ Discount Coupon',
                                                        'free_shipping' => '🚚 Free Shipping',
                                                    ])
                                                    ->required()
                                                    ->live()
                                                    ->native(false),

                                                Forms\Components\TextInput::make('points_cost')
                                                    ->label('Points Cost')
                                                    ->required()
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->step(1)
                                                    ->suffix('pts')
                                                    ->helperText('How many points the customer needs to redeem'),

                                                Forms\Components\TextInput::make('sort_order')
                                                    ->label('Sort Order')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->helperText('Lower numbers appear first'),
                                            ]),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('discount_type')
                                                    ->label('Discount Type')
                                                    ->options([
                                                        'percentage' => 'Percentage (%)',
                                                        'fixed'      => 'Fixed Amount (' . store_currency_symbol() . ')',
                                                    ])
                                                    ->default('percentage')
                                                    ->visible(fn(Schemas\Components\Utilities\Get $get): bool => $get('type') === 'discount'),

                                                Forms\Components\TextInput::make('discount_value')
                                                    ->label('Discount Value')
                                                    ->numeric()
                                                    ->minValue(0.01)
                                                    ->step(0.01)
                                                    ->prefix(fn(Schemas\Components\Utilities\Get $get) => $get('discount_type') === 'percentage' ? '%' : store_currency_symbol())
                                                    ->helperText('The amount or percentage off')
                                                    ->visible(fn(Schemas\Components\Utilities\Get $get): bool => $get('type') === 'discount')
                                                    ->rules([
                                                        fn(Schemas\Components\Utilities\Get $get) => function (string $attribute, $value, $fail) use ($get) {
                                                            if ($get('discount_type') === 'percentage' && $value > 100) {
                                                                $fail('Percentage discount cannot exceed 100%.');
                                                            }
                                                        },
                                                    ]),
                                            ]),

                                        Forms\Components\FileUpload::make('image')
                                            ->label('Reward Image (Optional)')
                                            ->image()
                                            ->directory('loyalty-rewards')
                                            ->maxSize(2048)
                                            ->helperText('Optional image for the reward card. Max 2MB.'),

                                        Forms\Components\Select::make('applicable_shipping_method_ids')
                                            ->label('Applicable Shipping Methods')
                                            ->multiple()
                                            ->options(fn() => ShippingMethod::active()
                                                ->get()
                                                ->mapWithKeys(fn($m) => [
                                                    $m->id => $m->getTranslation('name', 'en') . ' (' . ucfirst($m->carrier_type) . ' — ' . store_currency_symbol() . ' ' . number_format((float)$m->base_cost, 2) . ')',
                                                ])
                                                ->all()
                                            )
                                            ->visible(fn(Schemas\Components\Utilities\Get $get): bool => $get('type') === 'free_shipping')
                                            ->helperText('Select which shipping methods this free shipping reward applies to. Leave empty for ALL methods.')
                                            ->native(false)
                                            ->searchable(),
                                    ]),
                            ])
                            ->columnSpan(2),

                        // ── SIDEBAR (right 1/3) ──
                        Schemas\Components\Group::make()
                            ->schema([
                                Schemas\Components\Section::make('Status')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Active')
                                            ->default(true)
                                            ->helperText('Only active rewards are visible to customers'),
                                    ]),

                                Schemas\Components\Section::make('Preview')
                                    ->schema([
                                        Forms\Components\Placeholder::make('preview_type')
                                            ->label('Type')
                                            ->content(fn(?LoyaltyReward $record): string => $record
                                                ? match($record->type) {
                                                    'discount' => '🏷️ Discount',
                                                    'free_shipping' => '🚚 Free Shipping',
                                                    default => $record->type,
                                                }
                                                : '—'),

                                        Forms\Components\Placeholder::make('preview_cost')
                                            ->label('Points Required')
                                            ->content(fn(?LoyaltyReward $record): string => $record
                                                ? number_format($record->points_cost) . ' pts'
                                                : '—'),

                                        Forms\Components\Placeholder::make('preview_value')
                                            ->label('Discount')
                                            ->content(fn(?LoyaltyReward $record): string => $record && $record->discount_value
                                                ? ($record->discount_type === 'percentage'
                                                    ? $record->discount_value . '%'
                                                    : store_currency_symbol() . ' ' . number_format((float) $record->discount_value, 2))
                                                : '—'),

                                        Forms\Components\Placeholder::make('created_at_display')
                                            ->label('Created')
                                            ->content(fn(?LoyaltyReward $record): string => $record?->created_at
                                                ? $record->created_at->diffForHumans()
                                                : '—'),
                                    ])
                                    ->hiddenOn('create'),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable()
                    ->extraAttributes(['style' => 'font-family: monospace; font-weight: 600;']),

                Tables\Columns\TextColumn::make('name_en')
                    ->label('Name')
                    ->getStateUsing(fn(LoyaltyReward $record): string => $record->getTranslation('name', 'en'))
                    ->searchable(query: fn(Builder $query, string $search) => $query->where('name->en', 'like', "%{$search}%"))
                    ->limit(40),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match($state) {
                        'discount' => '🏷️ Discount',
                        'free_shipping' => '🚚 Free Shipping',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match($state) {
                        'discount' => 'warning',
                        'free_shipping' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('points_cost')
                    ->label('Points Cost')
                    ->sortable()
                    ->suffix(' pts')
                    ->numeric(),

                Tables\Columns\TextColumn::make('discount_display')
                    ->label('Value')
                    ->getStateUsing(function (LoyaltyReward $record): string {
                        if (!$record->discount_value) return '—';
                        return $record->discount_type === 'percentage'
                            ? $record->discount_value . '% OFF'
                            : store_currency_symbol() . ' ' . number_format((float) $record->discount_value, 2) . ' OFF';
                    })
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('type')
            ->groups([
                Tables\Grouping\Group::make('type')
                    ->label('Reward Type')
                    ->getTitleFromRecordUsing(fn(LoyaltyReward $record): string => match($record->type) {
                        'discount' => '🏷️ Discount Coupons',
                        'free_shipping' => '🚚 Free Shipping',
                        default => $record->type,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'discount'      => 'Discount',
                        'free_shipping' => 'Free Shipping',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->icon('heroicon-o-pencil'),

                Actions\Action::make('toggle_active')
                    ->label(fn(LoyaltyReward $record): string => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn(LoyaltyReward $record): string => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn(LoyaltyReward $record): string => $record->is_active ? 'warning' : 'success')
                    ->action(fn(LoyaltyReward $record) => $record->update(['is_active' => !$record->is_active]))
                    ->requiresConfirmation(),

                Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (LoyaltyReward $record): void {
                        $new = $record->replicate();
                        $new->slug = $record->slug . '-copy-' . now()->timestamp;
                        $new->save();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Duplicate Reward')
                    ->modalDescription('A new reward will be created with a unique slug.'),

                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(fn($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLoyaltyRewards::route('/'),
            'create' => Pages\CreateLoyaltyReward::route('/create'),
            'edit'   => Pages\EditLoyaltyReward::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('settings.view'));
    }
}

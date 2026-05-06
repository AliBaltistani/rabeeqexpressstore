<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static string|UnitEnum|null $navigationGroup = 'Promotions';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'code';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
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
                                // Coupon Details
                                Schemas\Components\Section::make('Coupon Details')
                                    ->schema([
                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('code')
                                                    ->label('Coupon Code')
                                                    ->required()
                                                    ->unique(Coupon::class, 'code', ignoreRecord: true)
                                                    ->maxLength(50)
                                                    ->dehydrateStateUsing(fn(?string $state) => $state ? strtoupper($state) : null)
                                                    ->extraInputAttributes(['style' => 'text-transform: uppercase; font-family: monospace; font-weight: bold; color: #dc2626;'])
                                                    ->suffixAction(
                                                        Actions\Action::make('generateCode')
                                                            ->icon('heroicon-o-arrow-path')
                                                            ->label('Generate')
                                                            ->action(function (Schemas\Components\Utilities\Set $set) {
                                                                $set('code', strtoupper(Str::random(8)));
                                                            })
                                                    ),

                                                Forms\Components\TextInput::make('name')
                                                    ->label('Display Name')
                                                    ->maxLength(255)
                                                    ->placeholder('e.g. Welcome Discount'),
                                            ]),

                                        Forms\Components\Textarea::make('description')
                                            ->label('Description (internal only)')
                                            ->rows(2)
                                            ->maxLength(500),

                                        Schemas\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Select::make('type')
                                                    ->label('Discount Type')
                                                    ->options([
                                                        'percentage' => 'Percentage Discount',
                                                        'fixed' => 'Fixed Amount',
                                                        'free_shipping' => 'Free Shipping',
                                                    ])
                                                    ->required()
                                                    ->live(),

                                                Forms\Components\TextInput::make('value')
                                                    ->label('Value')
                                                    ->required()
                                                    ->numeric()
                                                    ->minValue(0.01)
                                                    ->step(0.01)
                                                    ->prefix(fn(Schemas\Components\Utilities\Get $get) => $get('type') === 'percentage' ? '%' : 'SAR')
                                                    ->rules([
                                                        fn(Schemas\Components\Utilities\Get $get) => function (string $attribute, $value, $fail) use ($get) {
                                                            if ($get('type') === 'percentage' && $value > 100) {
                                                                $fail('Percentage discount cannot exceed 100%.');
                                                            }
                                                        },
                                                    ])
                                                    ->visible(fn(Schemas\Components\Utilities\Get $get): bool => $get('type') !== 'free_shipping'),

                                                Forms\Components\TextInput::make('max_discount_amount')
                                                    ->label('Max Discount Amount')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->step(0.01)
                                                    ->prefix('SAR')
                                                    ->placeholder('No limit')
                                                    ->visible(fn(Schemas\Components\Utilities\Get $get): bool => $get('type') === 'percentage'),
                                            ]),

                                        Schemas\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('min_order_amount')
                                                    ->label('Min Order Amount')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->step(0.01)
                                                    ->prefix('SAR')
                                                    ->placeholder('No minimum')
                                                    ->default(0),

                                                Forms\Components\TextInput::make('usage_limit')
                                                    ->label('Usage Limit (Total)')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('Unlimited'),

                                                Forms\Components\TextInput::make('usage_limit_per_user')
                                                    ->label('Limit Per User')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('Unlimited'),
                                            ]),
                                    ]),

                                // Schedule & Restrictions
                                Schemas\Components\Section::make('Schedule & Restrictions')
                                    ->schema([
                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\DateTimePicker::make('starts_at')
                                                    ->label('Start Date')
                                                    ->placeholder('Immediate'),

                                                Forms\Components\DateTimePicker::make('expires_at')
                                                    ->label('Expiry Date')
                                                    ->placeholder('No expiry')
                                                    ->after('starts_at'),
                                            ]),

                                        Forms\Components\Select::make('applies_to')
                                            ->label('Applies To')
                                            ->options([
                                                'all' => 'All Products',
                                                'categories' => 'Specific Categories',
                                                'products' => 'Specific Products',
                                            ])
                                            ->default('all')
                                            ->required()
                                            ->live(),

                                        Forms\Components\Select::make('categories')
                                            ->label('Select Categories')
                                            ->relationship('categories', 'name')
                                            ->getOptionLabelFromRecordUsing(fn(Category $record) => $record->getTranslation('name', 'en'))
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->visible(fn(Schemas\Components\Utilities\Get $get): bool => $get('applies_to') === 'categories'),

                                        Forms\Components\Select::make('products')
                                            ->label('Select Products')
                                            ->relationship('products', 'name')
                                            ->getOptionLabelFromRecordUsing(fn(Product $record) => $record->getTranslation('name', 'en') . ' (' . $record->sku . ')')
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->visible(fn(Schemas\Components\Utilities\Get $get): bool => $get('applies_to') === 'products'),

                                        Forms\Components\Toggle::make('exclude_sale_items')
                                            ->label('Exclude Sale Items')
                                            ->default(false)
                                            ->helperText('If enabled, products already on sale will not be eligible for this coupon.'),
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
                                            ->helperText('Enable or disable this coupon'),
                                    ]),

                                Schemas\Components\Section::make('Usage Stats')
                                    ->schema([
                                        Forms\Components\Placeholder::make('usage_count_display')
                                            ->label('Times Used')
                                            ->content(fn(?Coupon $record): string => $record ? (string) $record->usage_count : '0'),

                                        Forms\Components\Placeholder::make('usage_limit_display')
                                            ->label('Usage Limit')
                                            ->content(fn(?Coupon $record): string => $record?->usage_limit ? (string) $record->usage_limit : 'Unlimited'),
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
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->extraAttributes(['style' => 'font-family: monospace; font-weight: bold; color: #dc2626; text-transform: uppercase;']),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('type_badge')
                    ->label('Type')
                    ->getStateUsing(function (Coupon $record): string {
                        return match ($record->type) {
                            'percentage' => number_format($record->value, 2) . '% OFF',
                            'fixed' => number_format($record->value, 2) . ' SAR OFF',
                            'free_shipping' => 'Free Shipping',
                            default => $record->type,
                        };
                    })
                    ->badge()
                    ->color(fn(Coupon $record): string => match ($record->type) {
                        'percentage' => 'success',
                        'fixed' => 'info',
                        'free_shipping' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('usage_display')
                    ->label('Usage')
                    ->getStateUsing(function (Coupon $record): string {
                        $used = $record->usage_count;
                        $limit = $record->usage_limit ?? '∞';
                        return "{$used}/{$limit}";
                    }),

                Tables\Columns\TextColumn::make('computed_status')
                    ->label('Status')
                    ->getStateUsing(function (Coupon $record): string {
                        if ($record->expires_at && $record->expires_at->isPast()) {
                            return 'Expired';
                        }
                        return $record->is_active ? 'Active' : 'Inactive';
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Active' => 'success',
                        'Inactive' => 'gray',
                        'Expired' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('M d, Y')
                    ->placeholder('Never')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'expired' => 'Expired',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'active' => $query->where('is_active', true)->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now())),
                            'inactive' => $query->where('is_active', false),
                            'expired' => $query->whereNotNull('expires_at')->where('expires_at', '<', now()),
                            default => $query,
                        };
                    }),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed Amount',
                        'free_shipping' => 'Free Shipping',
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),

                Actions\EditAction::make()
                    ->icon('heroicon-o-pencil'),

                Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (Coupon $record): void {
                        $newCoupon = $record->replicate();
                        $newCoupon->code = strtoupper(Str::random(8));
                        $newCoupon->usage_count = 0;
                        $newCoupon->save();

                        // Copy category/product relationships
                        $newCoupon->categories()->sync($record->categories->pluck('id'));
                        $newCoupon->products()->sync($record->products->pluck('id'));
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Duplicate Coupon')
                    ->modalDescription('A new coupon will be created with a random code and zero usage count.'),

                Actions\Action::make('toggle_active')
                    ->label(fn(Coupon $record): string => $record->is_active ? 'Pause' : 'Resume')
                    ->icon(fn(Coupon $record): string => $record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn(Coupon $record): string => $record->is_active ? 'warning' : 'success')
                    ->action(fn(Coupon $record) => $record->update(['is_active' => !$record->is_active]))
                    ->requiresConfirmation(),

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
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('coupons.view'));
    }
}

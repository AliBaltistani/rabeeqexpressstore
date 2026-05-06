<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FlashSaleResource\Pages;
use App\Models\FlashSale;
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
use UnitEnum;

class FlashSaleResource extends Resource
{
    protected static ?string $model = FlashSale::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static string|UnitEnum|null $navigationGroup = 'Promotions';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

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
                                // Flash Sale Details
                                Schemas\Components\Section::make('Flash Sale Details')
                                    ->schema([
                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('name.en')
                                                    ->label('Name (English)')
                                                    ->required()
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('name.ar')
                                                    ->label('Name (Arabic)')
                                                    ->maxLength(255)
                                                    ->extraInputAttributes(['dir' => 'rtl']),
                                            ]),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\DateTimePicker::make('starts_at')
                                                    ->label('Start Date & Time')
                                                    ->required()
                                                    ->native(false),

                                                Forms\Components\DateTimePicker::make('ends_at')
                                                    ->label('End Date & Time')
                                                    ->required()
                                                    ->after('starts_at')
                                                    ->native(false),
                                            ]),
                                    ]),

                                // Flash Sale Products
                                Schemas\Components\Section::make('Flash Sale Products')
                                    ->schema([
                                        Forms\Components\Repeater::make('products')
                                            ->relationship()
                                            ->schema([
                                                Forms\Components\Select::make('product_id')
                                                    ->label('Product')
                                                    ->relationship('product', 'name')
                                                    ->getOptionLabelFromRecordUsing(fn(Product $record) => $record->getTranslation('name', 'en') . ' (' . $record->sku . ')')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, Schemas\Components\Utilities\Set $set) {
                                                        if ($state) {
                                                            $product = Product::withoutGlobalScope('active')->find($state);
                                                            if ($product) {
                                                                $set('original_price', $product->price);
                                                            }
                                                        }
                                                    }),

                                                Forms\Components\Select::make('variant_id')
                                                    ->label('Variant (optional)')
                                                    ->relationship('variant', 'sku')
                                                    ->searchable()
                                                    ->placeholder('All variants'),

                                                Forms\Components\TextInput::make('sale_price')
                                                    ->label('Sale Price')
                                                    ->required()
                                                    ->numeric()
                                                    ->minValue(0.01)
                                                    ->step(0.01)
                                                    ->prefix('SAR'),

                                                Forms\Components\TextInput::make('original_price')
                                                    ->label('Original Price')
                                                    ->required()
                                                    ->numeric()
                                                    ->minValue(0.01)
                                                    ->step(0.01)
                                                    ->prefix('SAR'),

                                                Forms\Components\TextInput::make('quantity_limit')
                                                    ->label('Qty Limit')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('∞'),

                                                Forms\Components\Placeholder::make('sold_count_display')
                                                    ->label('Sold')
                                                    ->content(fn($record) => $record?->sold_count ?? 0),
                                            ])
                                            ->columns(6)
                                            ->collapsible()
                                            ->defaultItems(0)
                                            ->addActionLabel('+ Add Product')
                                            ->rules([
                                                fn() => function (string $attribute, $value, $fail) {
                                                    if (is_array($value)) {
                                                        foreach ($value as $item) {
                                                            if (isset($item['sale_price'], $item['original_price'])) {
                                                                if ((float) $item['sale_price'] >= (float) $item['original_price']) {
                                                                    $fail('Sale price must be less than the original price for all products.');
                                                                    return;
                                                                }
                                                            }
                                                        }
                                                    }
                                                },
                                            ]),
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
                                            ->helperText('Enable or disable this flash sale'),
                                    ]),

                                Schemas\Components\Section::make('Sale Period')
                                    ->schema([
                                        Forms\Components\Placeholder::make('current_status')
                                            ->label('Current Status')
                                            ->content(function (?FlashSale $record): \Illuminate\Support\HtmlString {
                                                if (!$record) {
                                                    return new \Illuminate\Support\HtmlString('<span class="text-gray-500">New</span>');
                                                }

                                                $now = now();
                                                if ($record->starts_at > $now) {
                                                    return new \Illuminate\Support\HtmlString(
                                                        '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Upcoming</span>'
                                                    );
                                                }
                                                if ($record->ends_at < $now) {
                                                    return new \Illuminate\Support\HtmlString(
                                                        '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Ended</span>'
                                                    );
                                                }
                                                return new \Illuminate\Support\HtmlString(
                                                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>'
                                                );
                                            }),

                                        Forms\Components\Placeholder::make('products_count')
                                            ->label('Products')
                                            ->content(fn(?FlashSale $record): string => $record ? (string) $record->products()->count() : '0'),
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->formatStateUsing(fn(FlashSale $record) => $record->getTranslation('name', 'en'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('name', 'like', "%{$search}%");
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Start')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('End')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('computed_status')
                    ->label('Status')
                    ->getStateUsing(function (FlashSale $record): string {
                        if (!$record->is_active) return 'Inactive';
                        $now = now();
                        if ($record->starts_at > $now) return 'Upcoming';
                        if ($record->ends_at < $now) return 'Ended';
                        return 'Active';
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Active' => 'success',
                        'Upcoming' => 'info',
                        'Ended' => 'danger',
                        'Inactive' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->defaultSort('starts_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'upcoming' => 'Upcoming',
                        'ended' => 'Ended',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $now = now();
                        return match ($data['value'] ?? null) {
                            'active' => $query->where('is_active', true)->where('starts_at', '<=', $now)->where('ends_at', '>=', $now),
                            'upcoming' => $query->where('starts_at', '>', $now),
                            'ended' => $query->where('ends_at', '<', $now),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Actions\EditAction::make(),
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
            'index' => Pages\ListFlashSales::route('/'),
            'create' => Pages\CreateFlashSale::route('/create'),
            'edit' => Pages\EditFlashSale::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('flash_sales.view'));
    }
}

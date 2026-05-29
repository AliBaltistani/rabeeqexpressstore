<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingMethodResource\Pages;
use App\Models\Country;
use App\Models\ShippingCarrier;
use App\Models\ShippingMethod;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ShippingMethodResource extends Resource
{
    protected static ?string $model = ShippingMethod::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return trans('admin.nav.settings');
    }

    public static function getNavigationLabel(): string
    {
        return 'Shipping Methods';
    }

    public static function getModelLabel(): string
    {
        return 'Shipping Method';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Shipping Methods';
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Components\Section::make('Method Details')
                    ->schema([
                        Components\Tabs::make('Translations')
                            ->tabs([
                                Components\Tabs\Tab::make('English')
                                    ->icon('heroicon-o-language')
                                    ->schema([
                                        Forms\Components\TextInput::make('name.en')
                                            ->required()
                                            ->maxLength(255)
                                            ->label('Name (English)')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, $set, ?string $operation) {
                                                if ($operation === 'create' && $state) {
                                                    $set('slug', Str::slug($state));
                                                }
                                            }),
                                        Forms\Components\Textarea::make('description.en')
                                            ->rows(2)
                                            ->label('Description (English)'),
                                    ]),
                                Components\Tabs\Tab::make('Arabic')
                                    ->icon('heroicon-o-language')
                                    ->schema([
                                        Forms\Components\TextInput::make('name.ar')
                                            ->maxLength(255)
                                            ->extraInputAttributes(['dir' => 'rtl'])
                                            ->label('Name (Arabic)'),
                                        Forms\Components\Textarea::make('description.ar')
                                            ->rows(2)
                                            ->extraInputAttributes(['dir' => 'rtl'])
                                            ->label('Description (Arabic)'),
                                    ]),
                            ])
                            ->columnSpanFull(),

                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Auto-generated from name. You can customize it.')
                                    ->dehydrateStateUsing(fn(?string $state, $get) => $state ?: Str::slug($get('name.en') ?? '')),

                                Forms\Components\Select::make('carrier_type')
                                    ->required()
                                    ->options(fn() => ShippingCarrier::active()->ordered()->pluck('name', 'code')->toArray())
                                    ->searchable()
                                    ->label('Shipping Carrier')
                                    ->helperText('Select the shipping company for this method.'),
                            ]),
                    ]),

                Components\Section::make('Pricing & Rates')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('base_cost')
                                    ->required()
                                    ->numeric()
                                    ->prefix(currency_symbol())
                                    ->default(0.00)
                                    ->step(0.01)
                                    ->label('Base Cost'),

                                Forms\Components\TextInput::make('min_order_for_free')
                                    ->numeric()
                                    ->prefix(currency_symbol())
                                    ->step(0.01)
                                    ->label('Free Shipping Above')
                                    ->helperText('Min order total for free shipping. Leave empty if never free.')
                                    ->placeholder('e.g. 200.00'),

                                Forms\Components\TextInput::make('estimated_days_min')
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('Est. Days (Min)')
                                    ->placeholder('e.g. 3'),
                            ]),

                        Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('min_weight')
                                    ->numeric()
                                    ->step(0.01)
                                    ->label('Min Weight (kg)')
                                    ->placeholder('Optional'),

                                Forms\Components\TextInput::make('max_weight')
                                    ->numeric()
                                    ->step(0.01)
                                    ->label('Max Weight (kg)')
                                    ->placeholder('Optional'),

                                Forms\Components\TextInput::make('estimated_days_max')
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('Est. Days (Max)')
                                    ->placeholder('e.g. 7'),
                            ]),
                    ]),

                Components\Section::make('Availability')
                    ->icon('heroicon-o-globe-alt')
                    ->schema([
                        Forms\Components\Select::make('supported_countries')
                            ->multiple()
                            ->options(Country::where('is_active', true)->pluck('name_en', 'code'))
                            ->searchable()
                            ->label('Supported Countries')
                            ->helperText('Leave empty to support all countries.')
                            ->columnSpanFull(),

                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Inactive methods won\'t appear at checkout.'),

                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->formatStateUsing(fn(ShippingMethod $record) => $record->getTranslation('name', app()->getLocale()))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('carrier_type')
                    ->label('Carrier')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(function (string $state): string {
                        $carrier = ShippingCarrier::where('code', $state)->first();
                        return $carrier ? $carrier->name : ucfirst($state);
                    }),

                Tables\Columns\TextColumn::make('base_cost')
                    ->label('Price')
                    ->money(currency_code())
                    ->sortable(),

                Tables\Columns\TextColumn::make('min_order_for_free')
                    ->label('Free Above')
                    ->money(currency_code())
                    ->placeholder('Never')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estimated_delivery')
                    ->label('Est. Delivery')
                    ->getStateUsing(fn(ShippingMethod $record) => $record->getEstimatedDeliveryLabel() ?: '—'),

                Tables\Columns\TextColumn::make('countries_count')
                    ->label('Countries')
                    ->getStateUsing(function (ShippingMethod $record) {
                        $countries = $record->supported_countries;
                        if (empty($countries)) {
                            return 'All';
                        }
                        return count($countries) . ' countries';
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('carrier_type')
                    ->label('Carrier')
                    ->options(fn() => ShippingCarrier::active()->ordered()->pluck('name', 'code')->toArray()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListShippingMethods::route('/'),
            'create' => Pages\CreateShippingMethod::route('/create'),
            'edit'   => Pages\EditShippingMethod::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('settings.view'));
    }
}

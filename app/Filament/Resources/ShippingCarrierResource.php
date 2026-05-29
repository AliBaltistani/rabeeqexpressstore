<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingCarrierResource\Pages;
use App\Models\ShippingCarrier;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Schemas\Components;

class ShippingCarrierResource extends Resource
{
    protected static ?string $model = ShippingCarrier::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 7;

    public static function getNavigationGroup(): ?string
    {
        return trans('admin.nav.settings');
    }

    public static function getNavigationLabel(): string
    {
        return 'Shipping Companies';
    }

    public static function getModelLabel(): string
    {
        return 'Shipping Company';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Shipping Companies';
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Components\Section::make('Company Details')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Company Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g. SMSA Express'),

                                Forms\Components\TextInput::make('code')
                                    ->label('Short Code')
                                    ->required()
                                    ->maxLength(50)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('e.g. SMSA')
                                    ->helperText('Unique identifier used internally.'),
                            ]),

                        Forms\Components\FileUpload::make('logo')
                            ->label('Company Logo')
                            ->image()
                            ->directory('shipping-carriers')
                            ->disk('public')
                            ->maxSize(512)
                            ->nullable()
                            ->helperText('Optional. Max 512 KB.'),

                        Forms\Components\TextInput::make('tracking_url_template')
                            ->label('Tracking URL Template')
                            ->maxLength(500)
                            ->placeholder('https://example.com/track?number={tracking_number}')
                            ->helperText('Use **{tracking_number}** as placeholder for the actual tracking/AWB number.')
                            ->columnSpanFull(),

                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Inactive carriers won\'t appear in the tracking form.'),

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
                Tables\Columns\ImageColumn::make('logo')
                    ->label('')
                    ->circular()
                    ->size(32)
                    ->defaultImageUrl(fn () => ''),

                Tables\Columns\TextColumn::make('name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('tracking_url_template')
                    ->label('Tracking URL')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
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
            'index'  => Pages\ListShippingCarriers::route('/'),
            'create' => Pages\CreateShippingCarrier::route('/create'),
            'edit'   => Pages\EditShippingCarrier::route('/{record}/edit'),
        ];
    }
}

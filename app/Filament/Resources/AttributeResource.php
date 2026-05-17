<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttributeResource\Pages;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class AttributeResource extends Resource
{
    protected static ?string $model = ProductAttribute::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.attributes');
    }

    public static function getModelLabel(): string
    {
        return __('admin.attribute.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.attributes');
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Schemas\Components\Section::make(__('admin.attribute.info'))
                    ->schema([
                        Schemas\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name.en')
                                    ->label(__('admin.attribute.name_en'))
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('name.ar')
                                    ->label(__('admin.attribute.name_ar'))
                                    ->extraInputAttributes(['dir' => 'rtl'])
                                    ->maxLength(255),
                            ]),
                    ]),

                Schemas\Components\Section::make(__('admin.attribute.values'))
                    ->schema([
                        Forms\Components\Repeater::make('values')
                            ->relationship()
                            ->schema([
                                Schemas\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('value.en')
                                            ->label(__('admin.attribute.value_en'))
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('value.ar')
                                            ->label(__('admin.attribute.value_ar'))
                                            ->extraInputAttributes(['dir' => 'rtl'])
                                            ->maxLength(255),
                                    ]),
                            ])
                            ->collapsible()
                            ->defaultItems(1)
                            ->addActionLabel(__('admin.attribute.add_value'))
                            ->itemLabel(fn (array $state): ?string => $state['value']['en'] ?? null),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('admin.common.name'))
                    ->formatStateUsing(fn(ProductAttribute $record) => $record->getTranslation('name', 'en'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('values_count')
                    ->counts('values')
                    ->label(__('admin.attribute.values_count'))
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.common.date'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttributes::route('/'),
            'create' => Pages\CreateAttribute::route('/create'),
            'edit' => Pages\EditAttribute::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('products.view'));
    }
}

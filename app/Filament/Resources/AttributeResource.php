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
            ->columns(1)
            ->schema([
                Schemas\Components\Section::make(__('admin.attribute.info'))
                    ->schema([
                        Schemas\Components\Tabs::make('Attribute Name Translations')
                            ->tabs([
                                Schemas\Components\Tabs\Tab::make(__('admin.attribute.tab_english'))
                                    ->icon('heroicon-o-language')
                                    ->schema([
                                        Forms\Components\TextInput::make('name.en')
                                            ->label(__('admin.attribute.name_en'))
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('e.g. Color, Size, Material'),
                                    ]),

                                Schemas\Components\Tabs\Tab::make(__('admin.attribute.tab_arabic'))
                                    ->icon('heroicon-o-language')
                                    ->schema([
                                        Forms\Components\TextInput::make('name.ar')
                                            ->label(__('admin.attribute.name_ar'))
                                            ->extraInputAttributes(['dir' => 'rtl'])
                                            ->maxLength(255)
                                            ->placeholder('مثال: اللون، المقاس، الخامة'),
                                    ]),
                            ]),
                    ]),

                Schemas\Components\Section::make(__('admin.attribute.values'))
                    ->description(__('admin.attribute.values_help'))
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
                            ->cloneable()
                            ->reorderable()
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
                    ->description(fn(ProductAttribute $record): string => $record->getTranslation('name', 'ar') ?: '—')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('values_count')
                    ->counts('values')
                    ->label(__('admin.attribute.values_count'))
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('values_preview')
                    ->label(__('admin.attribute.values_preview'))
                    ->state(function (ProductAttribute $record) {
                        return $record->values
                            ->take(5)
                            ->map(fn($v) => $v->getTranslation('value', 'en'))
                            ->implode(', ') . ($record->values->count() > 5 ? ' …' : '');
                    })
                    ->wrap()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('categories_count')
                    ->counts('categories')
                    ->label(__('admin.attribute.used_in_categories'))
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.common.date'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make()
                    ->before(function (ProductAttribute $record) {
                        // Detach from all categories first
                        $record->categories()->detach();
                    }),
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

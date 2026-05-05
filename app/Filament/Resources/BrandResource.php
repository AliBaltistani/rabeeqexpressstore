<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Models\Brand;
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

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bookmark';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $form): Schema
    {
        return $form
            ->columns(1)
            ->schema([
                Schemas\Components\Grid::make(3)
                    ->schema([
                        Schemas\Components\Group::make()
                            ->schema([
                                Schemas\Components\Section::make('Brand Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true),

                                        Forms\Components\TextInput::make('slug')
                                            ->unique(Brand::class, 'slug', ignoreRecord: true)
                                            ->helperText('Auto-generated from name if left empty'),

                                        Forms\Components\FileUpload::make('logo')
                                            ->image()
                                            ->directory('brands')
                                            ->imageResizeMode('contain')
                                            ->imageResizeTargetWidth('300')
                                            ->imageResizeTargetHeight('300'),

                                        Forms\Components\Textarea::make('description')
                                            ->rows(3)
                                            ->maxLength(1000),

                                        Forms\Components\TextInput::make('sort_order')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0),
                                    ]),
                            ])
                            ->columnSpan(2),

                        Schemas\Components\Group::make()
                            ->schema([
                                Schemas\Components\Section::make('Status')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Active')
                                            ->default(true)
                                            ->helperText('Visible on the storefront'),
                                    ]),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular()
                    ->defaultImageUrl(fn() => 'https://ui-avatars.com/api/?name=B&background=6366f1&color=fff'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
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
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('brands.view'));
    }
}

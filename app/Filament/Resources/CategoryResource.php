<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
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

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.categories');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScope('active');
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->columns(1)
            ->schema([
                Schemas\Components\Grid::make(3)
                    ->schema([
                        // Left column (main content)
                        Schemas\Components\Group::make()
                            ->schema([
                                Forms\Components\Select::make('parent_id')
                                    ->label('Parent Category')
                                    ->relationship(
                                        'parent',
                                        'name',
                                        fn(Builder $query) => $query->withoutGlobalScope('active')
                                    )
                                    ->getOptionLabelFromRecordUsing(fn(Category $record) => $record->getTranslation('name', 'en'))
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('None (Root Category)')
                                    ->rules([
                                        fn(Schemas\Components\Utilities\Get $get) => function (string $attribute, $value, $fail) use ($get) {
                                            if ($value && $get('id') && $value == $get('id')) {
                                                $fail('A category cannot be its own parent.');
                                            }
                                            if ($value && $get('id')) {
                                                $children = Category::withoutGlobalScope('active')
                                                    ->where('parent_id', $get('id'))
                                                    ->pluck('id')
                                                    ->toArray();
                                                if (in_array($value, $children)) {
                                                    $fail('Cannot set a child category as parent (circular reference).');
                                                }
                                            }
                                        },
                                    ]),

                                Forms\Components\FileUpload::make('image')
                                    ->label('Category Image')
                                    ->image()
                                    ->directory('categories')
                                    ->imageResizeMode('cover')
                                    ->imageCropAspectRatio('3:2')
                                    ->imageResizeTargetWidth('600')
                                    ->imageResizeTargetHeight('400')
                                    ->helperText('Recommended: 600×400px'),

                                Forms\Components\TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),

                                // Tabbed translatable fields
                                Schemas\Components\Tabs::make('Translations')
                                    ->tabs([
                                        Schemas\Components\Tabs\Tab::make('English')
                                            ->icon('heroicon-o-language')
                                            ->schema([
                                                Forms\Components\TextInput::make('name.en')
                                                    ->label('Name (English)')
                                                    ->required()
                                                    ->maxLength(255),

                                                Forms\Components\RichEditor::make('description.en')
                                                    ->label('Description (English)')
                                                    ->toolbarButtons([
                                                        'bold',
                                                        'italic',
                                                        'underline',
                                                        'strike',
                                                        'bulletList',
                                                        'orderedList',
                                                        'link',
                                                        'blockquote',
                                                        'h2',
                                                        'h3',
                                                        'undo',
                                                        'redo',
                                                    ]),

                                                Forms\Components\TextInput::make('slug')
                                                    ->label('Slug')
                                                    ->unique(Category::class, 'slug', ignoreRecord: true)
                                                    ->helperText('Auto-generated from name if left empty'),
                                            ]),

                                        Schemas\Components\Tabs\Tab::make('Arabic')
                                            ->icon('heroicon-o-language')
                                            ->schema([
                                                Forms\Components\TextInput::make('name.ar')
                                                    ->label('Name (Arabic)')
                                                    ->extraInputAttributes(['dir' => 'rtl']),

                                                Forms\Components\RichEditor::make('description.ar')
                                                    ->label('Description (Arabic)')
                                                    ->extraInputAttributes(['dir' => 'rtl'])
                                                    ->toolbarButtons([
                                                        'bold',
                                                        'italic',
                                                        'underline',
                                                        'strike',
                                                        'bulletList',
                                                        'orderedList',
                                                        'link',
                                                        'blockquote',
                                                        'h2',
                                                        'h3',
                                                        'undo',
                                                        'redo',
                                                    ]),
                                            ]),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(2),

                        // Right sidebar
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
                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->circular()
                    ->defaultImageUrl(fn() => 'https://ui-avatars.com/api/?name=C&background=f59e0b&color=fff'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->formatStateUsing(fn(Category $record) => $record->getTranslation('name', 'en'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('name', 'like', "%{$search}%");
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent')
                    ->formatStateUsing(fn($state, Category $record) => $record->parent?->getTranslation('name', 'en') ?? '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(admin_date_format())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('Parent Category')
                    ->relationship('parent', 'name', fn(Builder $query) => $query->withoutGlobalScope('active'))
                    ->getOptionLabelFromRecordUsing(fn(Category $record) => $record->getTranslation('name', 'en'))
                    ->searchable()
                    ->preload(),

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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('categories.view'));
    }
}

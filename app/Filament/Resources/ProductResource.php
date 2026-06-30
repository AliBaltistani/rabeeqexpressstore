<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\Setting;
use App\Models\Tag;
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

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.products');
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
                        // ── MAIN CONTENT (left 2/3) ──
                        Schemas\Components\Group::make()
                            ->schema([
                                // General Information
                                Schemas\Components\Section::make('General Information')
                                    ->schema([
                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('name.en')
                                                    ->label('Product Name (English)')
                                                    ->required()
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('name.ar')
                                                    ->label('Product Name (Arabic)')
                                                    ->extraInputAttributes(['dir' => 'rtl'])
                                                    ->maxLength(255),
                                            ]),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('slug')
                                                    ->unique(Product::class, 'slug', ignoreRecord: true)
                                                    ->helperText('Auto-generated from name if left empty'),

                                                Forms\Components\TextInput::make('sku')
                                                    ->label('SKU')
                                                    ->unique(Product::class, 'sku', ignoreRecord: true)
                                                    ->required()
                                                    ->maxLength(100)
                                                    ->suffixAction(
                                                        Actions\Action::make('generateSku')
                                                            ->icon('heroicon-o-arrow-path')
                                                            ->action(function (Schemas\Components\Utilities\Set $set) {
                                                                $set('sku', 'PRD-' . strtoupper(Str::random(8)));
                                                            })
                                                    ),
                                            ]),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('category_id')
                                                    ->label('Category')
                                                    ->options(fn() => Category::getHierarchicalOptions())
                                                    ->searchable()
                                                    ->required()
                                                    ->live(),

                                                Forms\Components\Select::make('brand_id')
                                                    ->label('Brand')
                                                    ->relationship('brand', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('No brand'),
                                            ]),

                                    ]),

                                // Description
                                Schemas\Components\Section::make('Description')
                                    ->schema([
                                        Schemas\Components\Tabs::make('Description')
                                            ->tabs([
                                                Schemas\Components\Tabs\Tab::make('English')
                                                    ->icon('heroicon-o-language')
                                                    ->schema([
                                                        Forms\Components\Textarea::make('short_description.en')
                                                            ->label('Short Description')
                                                            ->rows(2)
                                                            ->maxLength(500),

                                                        Forms\Components\RichEditor::make('description.en')
                                                            ->label('Full Description')
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

                                                Schemas\Components\Tabs\Tab::make('Arabic')
                                                    ->icon('heroicon-o-language')
                                                    ->schema([
                                                        Forms\Components\Textarea::make('short_description.ar')
                                                            ->label('Short Description')
                                                            ->rows(2)
                                                            ->extraInputAttributes(['dir' => 'rtl'])
                                                            ->maxLength(500),

                                                        Forms\Components\RichEditor::make('description.ar')
                                                            ->label('Full Description')
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
                                            ]),
                                    ]),

                                // Pricing
                                Schemas\Components\Section::make('Pricing')
                                    ->schema([
                                        Schemas\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('price')
                                                    ->label('Price')
                                                    ->required()
                                                    ->numeric()
                                                    ->prefix(currency_symbol())
                                                    ->minValue(0)
                                                    ->step(0.01),

                                                Forms\Components\TextInput::make('compare_price')
                                                    ->label('Compare Price')
                                                    ->numeric()
                                                    ->prefix(currency_symbol())
                                                    ->minValue(0)
                                                    ->step(0.01)
                                                    ->helperText('Shown as strikethrough on frontend'),

                                                Forms\Components\TextInput::make('cost_price')
                                                    ->label('Cost Price')
                                                    ->numeric()
                                                    ->prefix(currency_symbol())
                                                    ->minValue(0)
                                                    ->step(0.01)
                                                    ->helperText('Internal only — not visible to customers'),
                                            ]),

                                        Forms\Components\Placeholder::make('pricing_note')
                                            ->content('Compare price must be higher than price for the sale badge to appear.')
                                            ->extraAttributes(['class' => 'text-sm text-gray-500']),
                                    ]),

                                // Inventory
                                Schemas\Components\Section::make('Inventory')
                                    ->schema([
                                        Forms\Components\Toggle::make('track_stock')
                                            ->label('Track stock quantity')
                                            ->default(true)
                                            ->live(),

                                        Schemas\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('stock_quantity')
                                                    ->label('Stock Quantity')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0),

                                                Forms\Components\TextInput::make('low_stock_threshold')
                                                    ->label('Low Stock Threshold')
                                                    ->numeric()
                                                    ->default(fn () => (int) setting('general.low_stock_threshold', 5))
                                                    ->minValue(0)
                                                    ->helperText('Leave at default to use global setting'),

                                                Forms\Components\TextInput::make('weight')
                                                    ->label('Weight (' . setting('shipping.default_weight_unit', 'kg') . ')')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->step(0.01),
                                            ])
                                            ->visible(fn(Schemas\Components\Utilities\Get $get): bool => (bool) $get('track_stock')),

                                        Forms\Components\Toggle::make('allow_backorders')
                                            ->label('Allow backorders')
                                            ->default(false)
                                            ->visible(fn(Schemas\Components\Utilities\Get $get): bool => (bool) $get('track_stock')),
                                    ]),

                                // Images
                                Schemas\Components\Section::make('Images')
                                    ->schema([
                                        Forms\Components\Repeater::make('images')
                                            ->relationship()
                                            ->schema([
                                                Forms\Components\FileUpload::make('image_path')
                                                    ->label('Image')
                                                    ->image()
                                                    ->disk('public')
                                                    ->directory('products')
                                                    ->maxSize(4096)
                                                    ->moveFiles()            // move from livewire-tmp → products/ on save
                                                    ->preserveFilenames(false) // always use UUID-based filenames
                                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                                                    ->required(),

                                                Forms\Components\TextInput::make('alt_text')
                                                    ->label('Alt Text')
                                                    ->maxLength(255),

                                                Forms\Components\Toggle::make('is_primary')
                                                    ->label('Primary Image')
                                                    ->default(false),

                                                Forms\Components\Hidden::make('sort_order')
                                                    ->default(0),
                                            ])
                                            ->columns(3)
                                            ->reorderableWithDragAndDrop()
                                            ->collapsible()
                                            ->defaultItems(0)
                                            ->addActionLabel('Add Image')
                                            ->helperText('Max 4MB each. Accepted: JPG, PNG, WebP, GIF'),
                                    ]),

                                // Product Attributes (dynamic from category)
                                Schemas\Components\Section::make(__('admin.product.attributes'))
                                    ->description(__('admin.product.attributes_help'))
                                    ->schema(function (Schemas\Components\Utilities\Get $get): array {
                                        $categoryId = $get('category_id');
                                        if (!$categoryId) return [];

                                        $category = Category::withoutGlobalScope('active')->find($categoryId);
                                        if (!$category) return [];

                                        $attributes = $category->attributes()->with('values')->get();
                                        if ($attributes->isEmpty()) {
                                            return [
                                                Forms\Components\Placeholder::make('no_attributes')
                                                    ->content(__('admin.product.no_category_attributes')),
                                            ];
                                        }

                                        $fields = [];
                                        foreach ($attributes as $attr) {
                                            $attrNameEn = $attr->getTranslation('name', 'en');
                                            $attrNameAr = $attr->getTranslation('name', 'ar');
                                            $label = $attrNameAr ? "{$attrNameEn} / {$attrNameAr}" : $attrNameEn;

                                            $fields[] = Forms\Components\CheckboxList::make("dynamic_attributes.{$attr->id}")
                                                ->label($label)
                                                ->options(
                                                    $attr->values->mapWithKeys(function (ProductAttributeValue $val) {
                                                        $en = $val->getTranslation('value', 'en');
                                                        $ar = $val->getTranslation('value', 'ar');
                                                        return [$val->id => $ar ? "{$en} / {$ar}" : $en];
                                                    })->toArray()
                                                )
                                                ->columns(3)
                                                ->bulkToggleable();
                                        }

                                        return $fields;
                                    })
                                    ->visible(fn(Schemas\Components\Utilities\Get $get): bool => (bool) $get('category_id')),

                                // SEO
                                Schemas\Components\Section::make('SEO')
                                    ->schema([
                                        Forms\Components\TextInput::make('meta_title')
                                            ->label('Meta Title')
                                            ->maxLength(60)
                                            ->helperText(fn(?string $state): string => ($state ? strlen($state) : 0) . '/60 characters'),

                                        Forms\Components\Textarea::make('meta_description')
                                            ->label('Meta Description')
                                            ->rows(2)
                                            ->maxLength(160)
                                            ->helperText(fn(?string $state): string => ($state ? strlen($state) : 0) . '/160 characters'),

                                        Forms\Components\TextInput::make('meta_keywords')
                                            ->label('Meta Keywords')
                                            ->helperText('Comma-separated keywords'),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ])
                            ->columnSpan(2),

                        // ── SIDEBAR (right 1/3) ──
                        Schemas\Components\Group::make()
                            ->schema([
                                Schemas\Components\Section::make('Publish')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Active (visible on store)')
                                            ->default(true),

                                        Forms\Components\Checkbox::make('is_featured')
                                            ->label('Featured Product'),

                                        Forms\Components\Checkbox::make('is_new')
                                            ->label('Mark as New'),

                                        Forms\Components\TextInput::make('sort_order')
                                            ->label('Sort Order')
                                            ->numeric()
                                            ->default(0),
                                    ]),

                                Schemas\Components\Section::make('Tags')
                                    ->schema([
                                        Forms\Components\Select::make('tags')
                                            ->relationship('tags', 'name')
                                            ->getOptionLabelFromRecordUsing(fn(Tag $record) => $record->getTranslation('name', 'en'))
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name.en')
                                                    ->label('Name (English)')
                                                    ->required(),
                                                Forms\Components\TextInput::make('name.ar')
                                                    ->label('Name (Arabic)')
                                                    ->extraInputAttributes(['dir' => 'rtl']),
                                            ])
                                            ->createOptionUsing(function (array $data): int {
                                                $name = array_filter($data['name'] ?? []);
                                                $tag = Tag::create([
                                                    'name' => $name,
                                                ]);
                                                return $tag->id;
                                            }),
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
                Tables\Columns\ImageColumn::make('primaryImage.image_path')
                    ->label('Image')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn() => 'https://ui-avatars.com/api/?name=P&background=3b82f6&color=fff'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->formatStateUsing(function (Product $record) {
                        $name = $record->getTranslation('name', 'en');
                        $badges = '';
                        if ($record->is_featured) {
                            $badges .= ' <span style="background:#f59e0b;color:#fff;padding:1px 6px;border-radius:4px;font-size:11px;">Featured</span>';
                        }
                        if ($record->is_new) {
                            $badges .= ' <span style="background:#3b82f6;color:#fff;padding:1px 6px;border-radius:4px;font-size:11px;">New</span>';
                        }
                        return $name . $badges;
                    })
                    ->html()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->formatStateUsing(fn($state, Product $record) => $record->category?->getTranslation('name', 'en') ?? '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->formatStateUsing(function (Product $record) {
                        $symbol = currency_symbol();
                        $price = number_format((float) ($record->price ?? 0), 2) . ' ' . $symbol;
                        if ($record->compare_price && $record->compare_price > $record->price) {
                            $price .= ' <span style="text-decoration:line-through;color:#9ca3af;font-size:12px;">' . number_format((float) $record->compare_price, 2) . '</span>';
                        }
                        return $price;
                    })
                    ->html()
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->color(fn(Product $record): string => $record->stock_quantity <= $record->low_stock_threshold ? 'danger' : 'success')
                    ->badge()
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn() => Category::getHierarchicalOptions())
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(fn(Builder $query): Builder => $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold'))
                    ->toggle(),

                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn(Builder $query): Builder => $query->where('stock_quantity', 0))
                    ->toggle(),
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

                    Actions\BulkAction::make('feature')
                        ->label('Mark as Featured')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->action(fn($records) => $records->each->update(['is_featured' => true]))
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    Actions\BulkAction::make('unfeature')
                        ->label('Remove Featured')
                        ->icon('heroicon-o-star')
                        ->action(fn($records) => $records->each->update(['is_featured' => false]))
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('products.view'));
    }
}

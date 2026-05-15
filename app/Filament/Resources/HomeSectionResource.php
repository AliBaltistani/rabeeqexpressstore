<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomeSectionResource\Pages;
use App\Models\Category;
use App\Models\HomeSection;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HomeSectionResource extends Resource
{
    protected static ?string $model = HomeSection::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.content');
    }

    public static function getNavigationLabel(): string
    {
        return 'Homepage Sections';
    }

    public static function getModelLabel(): string
    {
        return 'Homepage Section';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Homepage Sections';
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
                                Schemas\Components\Section::make('Section Settings')
                                    ->schema([
                                        Forms\Components\Select::make('type')
                                            ->label('Section Type')
                                            ->options(HomeSection::TYPES)
                                            ->required()
                                            ->live()
                                            ->default('featured_products')
                                            ->helperText('Choose the type of content this section will display'),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('title.en')
                                                    ->label('Section Title (English)')
                                                    ->maxLength(255)
                                                    ->helperText('Displayed as the section heading'),

                                                Forms\Components\TextInput::make('title.ar')
                                                    ->label('Section Title (Arabic)')
                                                    ->maxLength(255)
                                                    ->extraInputAttributes(['dir' => 'rtl']),
                                            ]),

                                        Forms\Components\TextInput::make('config.subtitle_en')
                                            ->label('Subtitle (English)')
                                            ->maxLength(500)
                                            ->helperText('Optional description text below the title'),

                                        Forms\Components\TextInput::make('config.subtitle_ar')
                                            ->label('Subtitle (Arabic)')
                                            ->maxLength(500)
                                            ->extraInputAttributes(['dir' => 'rtl']),
                                    ]),

                                // ── Banner Section Config ──
                                Schemas\Components\Section::make('Banner Settings')
                                    ->schema([
                                        Forms\Components\Placeholder::make('banner_note')
                                            ->content('Banners are managed via the Banners resource. Configure display options below.'),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Toggle::make('config.autoplay')
                                                    ->label('Auto-play Slider')
                                                    ->default(true)
                                                    ->helperText('Automatically cycle through slides'),

                                                Forms\Components\TextInput::make('config.autoplay_speed')
                                                    ->label('Autoplay Speed (ms)')
                                                    ->numeric()
                                                    ->default(5000)
                                                    ->minValue(1000)
                                                    ->maxValue(30000)
                                                    ->helperText('Time between slides in milliseconds'),
                                            ]),

                                        Forms\Components\Toggle::make('config.show_navigation')
                                            ->label('Show Navigation Arrows')
                                            ->default(true),

                                        Forms\Components\Toggle::make('config.show_pagination')
                                            ->label('Show Pagination Dots')
                                            ->default(true),
                                    ])
                                    ->visible(fn(Schemas\Components\Utilities\Get $get) => $get('type') === 'hero_slider')
                                    ->collapsible(),

                                // ── Promo Banner Config ──
                                Schemas\Components\Section::make('Promo Banner Settings')
                                    ->schema([
                                        Forms\Components\Placeholder::make('promo_note')
                                            ->content('Promo banners are managed via the Banners resource (position: "promo"). Configure display below.'),

                                        Forms\Components\Select::make('config.columns')
                                            ->label('Columns per Row')
                                            ->options([
                                                2 => '2 Banners per row',
                                                3 => '3 Banners per row',
                                                4 => '4 Banners per row',
                                            ])
                                            ->default(2),

                                        Forms\Components\TextInput::make('config.max_banners')
                                            ->label('Maximum Banners to Show')
                                            ->numeric()
                                            ->default(4)
                                            ->minValue(1)
                                            ->maxValue(8),

                                        Forms\Components\TextInput::make('config.gap')
                                            ->label('Gap Between Banners (px)')
                                            ->numeric()
                                            ->default(16)
                                            ->minValue(0)
                                            ->maxValue(48),
                                    ])
                                    ->visible(fn(Schemas\Components\Utilities\Get $get) => $get('type') === 'promo_banners')
                                    ->collapsible(),

                                // ── Product Section Config ──
                                Schemas\Components\Section::make('Product Display Settings')
                                    ->schema([
                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('config.limit')
                                                    ->label('Number of Products')
                                                    ->numeric()
                                                    ->default(10)
                                                    ->minValue(1)
                                                    ->maxValue(50)
                                                    ->helperText('How many products to display'),

                                                Forms\Components\Select::make('config.layout')
                                                    ->label('Display Layout')
                                                    ->options([
                                                        'slider' => 'Horizontal Slider',
                                                        'grid' => 'Grid View',
                                                    ])
                                                    ->default('slider'),
                                            ]),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('config.products_per_row')
                                                    ->label('Products per Row (Grid)')
                                                    ->numeric()
                                                    ->default(5)
                                                    ->minValue(2)
                                                    ->maxValue(6),

                                                Forms\Components\Select::make('config.sort_by')
                                                    ->label('Sort Products By')
                                                    ->options([
                                                        'default' => 'Default',
                                                        'price_low' => 'Price: Low to High',
                                                        'price_high' => 'Price: High to Low',
                                                        'newest' => 'Newest First',
                                                        'rating' => 'Highest Rated',
                                                    ])
                                                    ->default('default'),
                                            ]),

                                        Forms\Components\Toggle::make('config.show_view_all')
                                            ->label('Show "View All" Link')
                                            ->default(true),

                                        Forms\Components\TextInput::make('config.view_all_url')
                                            ->label('Custom "View All" URL')
                                            ->placeholder('/products?featured=true')
                                            ->maxLength(500)
                                            ->helperText('Leave empty to use the default link'),
                                    ])
                                    ->visible(fn(Schemas\Components\Utilities\Get $get) => in_array($get('type'), [
                                        'featured_products',
                                        'best_sellers',
                                        'new_arrivals',
                                    ]))
                                    ->collapsible(),

                                // ── Category Products Config ──
                                Schemas\Components\Section::make('Category Product Settings')
                                    ->schema([
                                        Forms\Components\Select::make('config.category_slug')
                                            ->label('Category')
                                            ->options(
                                                fn() => Category::query()
                                                    ->where('is_active', true)
                                                    ->get()
                                                    ->mapWithKeys(fn(Category $cat) => [
                                                        $cat->slug => $cat->getTranslation('name', 'en') . ' (' . $cat->slug . ')',
                                                    ])
                                                    ->toArray()
                                            )
                                            ->searchable()
                                            ->required()
                                            ->helperText('Select which category products to display'),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('config.limit')
                                                    ->label('Number of Products')
                                                    ->numeric()
                                                    ->default(10)
                                                    ->minValue(1)
                                                    ->maxValue(50),

                                                Forms\Components\Select::make('config.layout')
                                                    ->label('Display Layout')
                                                    ->options([
                                                        'slider' => 'Horizontal Slider',
                                                        'grid' => 'Grid View',
                                                    ])
                                                    ->default('slider'),
                                            ]),

                                        Forms\Components\Toggle::make('config.show_view_all')
                                            ->label('Show "View All" Link')
                                            ->default(true),
                                    ])
                                    ->visible(fn(Schemas\Components\Utilities\Get $get) => $get('type') === 'category_products')
                                    ->collapsible(),

                                // ── Reviews Config ──
                                Schemas\Components\Section::make('Customer Reviews')
                                    ->schema([
                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('config.review_layout')
                                                    ->label('Display Style')
                                                    ->options([
                                                        'slider' => 'Slider / Carousel',
                                                        'grid' => 'Grid View',
                                                    ])
                                                    ->default('slider'),

                                                Forms\Components\TextInput::make('config.reviews_per_view')
                                                    ->label('Reviews Visible at Once')
                                                    ->numeric()
                                                    ->default(3)
                                                    ->minValue(1)
                                                    ->maxValue(6),
                                            ]),

                                        Forms\Components\Toggle::make('config.show_rating_stars')
                                            ->label('Show Rating Stars')
                                            ->default(true),

                                        Forms\Components\Toggle::make('config.show_avatars')
                                            ->label('Show Customer Avatars')
                                            ->default(true),

                                        Forms\Components\Repeater::make('config.reviews')
                                            ->label('Reviews')
                                            ->schema([
                                                Schemas\Components\Grid::make(3)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('name')
                                                            ->label('Customer Name')
                                                            ->required()
                                                            ->columnSpan(1),

                                                        Forms\Components\Select::make('rating')
                                                            ->label('Rating')
                                                            ->options([
                                                                5 => '★★★★★ (5)',
                                                                4 => '★★★★☆ (4)',
                                                                3 => '★★★☆☆ (3)',
                                                                2 => '★★☆☆☆ (2)',
                                                                1 => '★☆☆☆☆ (1)',
                                                            ])
                                                            ->default(5)
                                                            ->columnSpan(1),

                                                        Forms\Components\TextInput::make('avatar')
                                                            ->label('Avatar URL')
                                                            ->url()
                                                            ->placeholder('https://...')
                                                            ->columnSpan(1),
                                                    ]),

                                                Forms\Components\Textarea::make('text')
                                                    ->label('Review Text')
                                                    ->required()
                                                    ->rows(2),
                                            ])
                                            ->collapsible()
                                            ->reorderable()
                                            ->defaultItems(0)
                                            ->addActionLabel('Add Review')
                                            ->itemLabel(fn(array $state): ?string => ($state['name'] ?? '') . ' — ★' . ($state['rating'] ?? '5')),
                                    ])
                                    ->visible(fn(Schemas\Components\Utilities\Get $get) => $get('type') === 'reviews')
                                    ->collapsible(),
                            ])
                            ->columnSpan(2),

                        // ── SIDEBAR (right 1/3) ──
                        Schemas\Components\Group::make()
                            ->schema([
                                Schemas\Components\Section::make('Visibility')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Active')
                                            ->default(true)
                                            ->helperText('Show on homepage'),

                                        Forms\Components\TextInput::make('sort_order')
                                            ->label('Sort Order')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->helperText('Lower = appears first'),
                                    ]),

                                Schemas\Components\Section::make('Appearance')
                                    ->schema([
                                        Forms\Components\TextInput::make('config.css_class')
                                            ->label('Custom CSS Class')
                                            ->placeholder('e.g. my-custom-section')
                                            ->maxLength(255)
                                            ->helperText('Add to section wrapper'),

                                        Forms\Components\Select::make('config.background')
                                            ->label('Background Style')
                                            ->options([
                                                'default' => 'Default (White)',
                                                'light' => 'Light Gray',
                                                'primary' => 'Primary Color',
                                                'dark' => 'Dark',
                                            ])
                                            ->default('default'),

                                        Forms\Components\Select::make('config.padding')
                                            ->label('Section Padding')
                                            ->options([
                                                'none' => 'None',
                                                'sm' => 'Small',
                                                'md' => 'Medium',
                                                'lg' => 'Large',
                                            ])
                                            ->default('md'),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'hero_slider' => 'primary',
                        'promo_banners' => 'info',
                        'featured_products' => 'success',
                        'best_sellers' => 'warning',
                        'new_arrivals' => 'danger',
                        'category_products' => 'gray',
                        'reviews' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => HomeSection::TYPES[$state] ?? ucfirst($state)),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->formatStateUsing(fn(HomeSection $record) => $record->getTranslation('title', 'en') ?: '—')
                    ->limit(40),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),

                Tables\Columns\TextColumn::make('config_summary')
                    ->label('Config')
                    ->getStateUsing(function (HomeSection $record): string {
                        $config = $record->config ?? [];
                        $parts = [];
                        if (!empty($config['limit']))
                            $parts[] = "Limit: {$config['limit']}";
                        if (!empty($config['category_slug']))
                            $parts[] = "Cat: {$config['category_slug']}";
                        if (!empty($config['reviews']))
                            $parts[] = count($config['reviews']) . ' reviews';
                        if (!empty($config['layout']))
                            $parts[] = ucfirst($config['layout']);
                        if (!empty($config['background']) && $config['background'] !== 'default')
                            $parts[] = "BG: {$config['background']}";
                        return implode(' · ', $parts) ?: '—';
                    }),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->actions([
                Actions\EditAction::make(),
                Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (HomeSection $record) {
                        $new = $record->replicate();
                        $new->sort_order = HomeSection::max('sort_order') + 1;
                        $new->title = ['en' => $record->getTranslation('title', 'en') . ' (Copy)', 'ar' => $record->getTranslation('title', 'ar') . ' (نسخة)'];
                        $new->save();
                    }),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomeSections::route('/'),
            'create' => Pages\CreateHomeSection::route('/create'),
            'edit' => Pages\EditHomeSection::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('home_sections.view'));
    }
}

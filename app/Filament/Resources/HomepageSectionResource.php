<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomepageSectionResource\Pages;
use App\Models\Banner;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Models\Slider;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HomepageSectionResource extends Resource
{
    protected static ?string $model = HomepageSection::class;

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

    /**
     * Shared slider config fields reused by products & reviews sections.
     */
    private static function sliderConfigFields(): array
    {
        return [
            Schemas\Components\Grid::make(2)
                ->schema([
                    Forms\Components\Toggle::make('config.autoplay')
                        ->label('Autoplay')
                        ->default(true),

                    Forms\Components\TextInput::make('config.autoplay_delay')
                        ->label('Autoplay Delay (ms)')
                        ->numeric()
                        ->default(4000)
                        ->minValue(1000)
                        ->maxValue(30000),
                ]),

            Schemas\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('config.slides_per_view')
                        ->label('Items Per View')
                        ->numeric()
                        ->default(5)
                        ->minValue(1)
                        ->maxValue(10)
                        ->helperText('Number of visible items at once'),

                    Forms\Components\Toggle::make('config.loop')
                        ->label('Loop')
                        ->default(true)
                        ->helperText('Restart from beginning when reaching end'),
                ]),

            Forms\Components\Select::make('config.direction')
                ->label('Slide Direction')
                ->options([
                    'ltr' => 'Left to Right',
                    'rtl' => 'Right to Left',
                    'auto' => 'Auto (follows page language)',
                ])
                ->default('auto')
                ->helperText('Auto will slide RTL for Arabic and LTR for English'),
        ];
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
                                        Forms\Components\TextInput::make('name')
                                            ->label('Section Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->helperText('Internal label for admin identification'),

                                        Forms\Components\Select::make('type')
                                            ->label('Section Type')
                                            ->options(HomepageSection::TYPES)
                                            ->required()
                                            ->live()
                                            ->default('hero_slider')
                                            ->helperText('Choose the type of content this section will display'),
                                    ]),

                                // ═══════════════════════════════════════
                                // HERO SLIDER CONFIG
                                // ═══════════════════════════════════════
                                Schemas\Components\Section::make('Hero Slider Settings')
                                    ->schema([
                                        Forms\Components\Select::make('config.slider_ids')
                                            ->label('Sliders')
                                            ->multiple()
                                            ->options(
                                                fn() => Slider::query()
                                                    ->orderBy('sort_order')
                                                    ->get()
                                                    ->mapWithKeys(fn(Slider $s) => [
                                                        $s->id => $s->getTranslation('title', 'en') ?: "Slider #{$s->id}",
                                                    ])
                                                    ->toArray()
                                            )
                                            ->searchable()
                                            ->helperText('Select sliders to display in this section'),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Toggle::make('config.show_indicators')
                                                    ->label('Show Indicators')
                                                    ->default(true),

                                                Forms\Components\Select::make('config.indicator_position')
                                                    ->label('Indicator Position')
                                                    ->options([
                                                        'bottom' => 'Bottom',
                                                        'top' => 'Top',
                                                    ])
                                                    ->default('bottom'),
                                            ]),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('config.navigation_style')
                                                    ->label('Navigation Style')
                                                    ->options([
                                                        'arrows' => 'Arrows',
                                                        'dots' => 'Dots',
                                                        'both' => 'Both',
                                                        'none' => 'None',
                                                    ])
                                                    ->default('arrows'),

                                                Forms\Components\Select::make('config.navigation_position')
                                                    ->label('Navigation Position')
                                                    ->options([
                                                        'inside' => 'Inside',
                                                        'outside' => 'Outside',
                                                    ])
                                                    ->default('inside'),
                                            ]),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('config.height')
                                                    ->label('Height (px)')
                                                    ->numeric()
                                                    ->default(400)
                                                    ->minValue(100)
                                                    ->maxValue(1200),

                                                Forms\Components\Select::make('config.width')
                                                    ->label('Width')
                                                    ->options([
                                                        'full' => 'Full Width',
                                                        'contained' => 'Contained',
                                                    ])
                                                    ->default('full'),
                                            ]),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Toggle::make('config.autoplay')
                                                    ->label('Autoplay')
                                                    ->default(true),

                                                Forms\Components\TextInput::make('config.autoplay_delay')
                                                    ->label('Autoplay Delay (ms)')
                                                    ->numeric()
                                                    ->default(5000)
                                                    ->minValue(1000)
                                                    ->maxValue(30000)
                                                    ->helperText('Time between slides'),
                                            ]),
                                    ])
                                    ->visible(fn(Schemas\Components\Utilities\Get $get) => $get('type') === 'hero_slider')
                                    ->collapsible(),

                                // ═══════════════════════════════════════
                                // BANNER CONFIG
                                // ═══════════════════════════════════════
                                Schemas\Components\Section::make('Banner Settings')
                                    ->schema([
                                        Forms\Components\Select::make('config.banner_ids')
                                            ->label('Banners')
                                            ->multiple()
                                            ->options(
                                                fn() => Banner::query()
                                                    ->orderBy('sort_order')
                                                    ->get()
                                                    ->mapWithKeys(fn(Banner $b) => [
                                                        $b->id => $b->getTranslation('title', 'en') ?: "Banner #{$b->id}",
                                                    ])
                                                    ->toArray()
                                            )
                                            ->searchable()
                                            ->helperText('Select banners to display'),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('config.cols')
                                                    ->label('Columns')
                                                    ->numeric()
                                                    ->default(2)
                                                    ->minValue(1)
                                                    ->maxValue(6),

                                                Forms\Components\TextInput::make('config.rows')
                                                    ->label('Rows')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(1)
                                                    ->maxValue(6),
                                            ]),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('config.gap')
                                                    ->label('Gap')
                                                    ->options([
                                                        'none' => 'None',
                                                        'sm' => 'Small',
                                                        'md' => 'Medium',
                                                        'lg' => 'Large',
                                                    ])
                                                    ->default('md'),

                                                Forms\Components\Select::make('config.position')
                                                    ->label('Position')
                                                    ->options([
                                                        'full-width' => 'Full Width',
                                                        'contained' => 'Contained',
                                                        'left' => 'Left',
                                                        'right' => 'Right',
                                                    ])
                                                    ->default('full-width'),
                                            ]),
                                    ])
                                    ->visible(fn(Schemas\Components\Utilities\Get $get) => $get('type') === 'banner')
                                    ->collapsible(),

                                // ═══════════════════════════════════════
                                // PRODUCTS CONFIG
                                // ═══════════════════════════════════════
                                Schemas\Components\Section::make('Products Settings')
                                    ->schema([
                                        Forms\Components\Select::make('config.product_ids')
                                            ->label('Products')
                                            ->multiple()
                                            ->searchable()
                                            ->getSearchResultsUsing(function (string $search): array {
                                                return Product::withoutGlobalScope('active')
                                                    ->where('is_active', true)
                                                    ->where(function ($q) use ($search) {
                                                        $q->where('name', 'like', "%{$search}%")
                                                          ->orWhere('sku', 'like', "%{$search}%");
                                                    })
                                                    ->limit(50)
                                                    ->get()
                                                    ->mapWithKeys(fn(Product $p) => [
                                                        $p->id => $p->getTranslation('name', 'en') . " (SKU: {$p->sku})",
                                                    ])
                                                    ->toArray();
                                            })
                                            ->getOptionLabelsUsing(function (array $values): array {
                                                return Product::withoutGlobalScope('active')
                                                    ->whereIn('id', $values)
                                                    ->get()
                                                    ->mapWithKeys(fn(Product $p) => [
                                                        $p->id => $p->getTranslation('name', 'en') . " (SKU: {$p->sku})",
                                                    ])
                                                    ->toArray();
                                            })
                                            ->helperText('Search and select products to display'),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('config.title_en')
                                                    ->label('Section Title (English)')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('config.title_ar')
                                                    ->label('Section Title (Arabic)')
                                                    ->maxLength(255)
                                                    ->extraInputAttributes(['dir' => 'rtl']),
                                            ]),

                                        Forms\Components\Select::make('config.display_mode')
                                            ->label('Display Mode')
                                            ->options([
                                                'grid' => 'Grid (static)',
                                                'slider' => 'Slider (animated)',
                                            ])
                                            ->default('slider')
                                            ->live()
                                            ->helperText('Grid shows all products in a static grid. Slider scrolls products one by one.'),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('config.cols')
                                                    ->label('Columns (2–6)')
                                                    ->numeric()
                                                    ->default(4)
                                                    ->minValue(2)
                                                    ->maxValue(6),

                                                Forms\Components\Toggle::make('config.show_price')
                                                    ->label('Show Price')
                                                    ->default(true),
                                            ]),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Toggle::make('config.show_badge')
                                                    ->label('Show Badge')
                                                    ->default(true),

                                                Forms\Components\Toggle::make('config.show_add_to_cart')
                                                    ->label('Show Add to Cart')
                                                    ->default(true),
                                            ]),
                                    ])
                                    ->visible(fn(Schemas\Components\Utilities\Get $get) => $get('type') === 'products')
                                    ->collapsible(),

                                // ── Product Slider Options (shown only when display_mode = slider) ──
                                Schemas\Components\Section::make('Product Slider Options')
                                    ->schema(static::sliderConfigFields())
                                    ->visible(fn(Schemas\Components\Utilities\Get $get) =>
                                        $get('type') === 'products' &&
                                        ($get('config.display_mode') ?? 'slider') === 'slider'
                                    )
                                    ->collapsible()
                                    ->collapsed(),

                                // ═══════════════════════════════════════
                                // REVIEWS CONFIG
                                // ═══════════════════════════════════════
                                Schemas\Components\Section::make('Customer Reviews Settings')
                                    ->schema([
                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('config.title_en')
                                                    ->label('Section Title (English)')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('config.title_ar')
                                                    ->label('Section Title (Arabic)')
                                                    ->maxLength(255)
                                                    ->extraInputAttributes(['dir' => 'rtl']),
                                            ]),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Toggle::make('config.show_rating_stars')
                                                    ->label('Show Rating Stars')
                                                    ->default(true),

                                                Forms\Components\Toggle::make('config.show_avatars')
                                                    ->label('Show Customer Avatars')
                                                    ->default(true),
                                            ]),

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
                                            ->itemLabel(fn(array $state): ?string =>
                                                ($state['name'] ?? '') . ' — ★' . ($state['rating'] ?? '5')
                                            ),
                                    ])
                                    ->visible(fn(Schemas\Components\Utilities\Get $get) => $get('type') === 'reviews')
                                    ->collapsible(),

                                // ── Reviews Slider Options ──
                                Schemas\Components\Section::make('Reviews Slider Options')
                                    ->schema(static::sliderConfigFields())
                                    ->visible(fn(Schemas\Components\Utilities\Get $get) => $get('type') === 'reviews')
                                    ->collapsible()
                                    ->collapsed(),

                                // ═══════════════════════════════════════
                                // CUSTOM HTML CONFIG
                                // ═══════════════════════════════════════
                                Schemas\Components\Section::make('Custom HTML Settings')
                                    ->schema([
                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('config.title_en')
                                                    ->label('Title (English)')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('config.title_ar')
                                                    ->label('Title (Arabic)')
                                                    ->maxLength(255)
                                                    ->extraInputAttributes(['dir' => 'rtl']),
                                            ]),

                                        Forms\Components\Textarea::make('config.content')
                                            ->label('HTML Content')
                                            ->rows(10)
                                            ->helperText('Raw HTML or Vue component string — rendered as-is on the frontend'),
                                    ])
                                    ->visible(fn(Schemas\Components\Utilities\Get $get) => $get('type') === 'custom_html')
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
                                            ->default(fn() => (HomepageSection::max('sort_order') ?? 0) + 1)
                                            ->minValue(0)
                                            ->helperText('Lower = appears first'),
                                    ]),

                                // ═══════════════════════════════════════
                                // COMMON APPEARANCE SETTINGS
                                // ═══════════════════════════════════════
                                Schemas\Components\Section::make('Section Layout')
                                    ->schema([
                                        Forms\Components\Select::make('config.section_width')
                                            ->label('Width')
                                            ->options([
                                                'full-width' => 'Full Width',
                                                'contained'  => 'Contained',
                                            ])
                                            ->default('contained')
                                            ->helperText('Full width stretches edge-to-edge; Contained limits to max content width'),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),

                                Schemas\Components\Section::make('Section Title')
                                    ->schema([
                                        Forms\Components\Toggle::make('config.show_title')
                                            ->label('Show Title')
                                            ->default(true),

                                        Forms\Components\Select::make('config.title_alignment')
                                            ->label('Title Alignment')
                                            ->options([
                                                'left'   => 'Left',
                                                'center' => 'Center',
                                                'right'  => 'Right',
                                            ])
                                            ->default('center'),
                                    ])
                                    ->visible(fn(Schemas\Components\Utilities\Get $get) => in_array($get('type'), ['products', 'reviews', 'custom_html']))
                                    ->collapsible()
                                    ->collapsed(),

                                Schemas\Components\Section::make('Slider Arrows')
                                    ->schema([
                                        Forms\Components\Toggle::make('config.show_arrows')
                                            ->label('Show Arrows')
                                            ->default(true),

                                        Forms\Components\Select::make('config.arrows_style')
                                            ->label('Arrow Style')
                                            ->options([
                                                'rounded' => 'Rounded',
                                                'square'  => 'Square',
                                                'minimal' => 'Minimal',
                                            ])
                                            ->default('rounded'),

                                        Forms\Components\Select::make('config.arrows_position')
                                            ->label('Arrow Position')
                                            ->options([
                                                'inside'        => 'Both Sides — Center (inside)',
                                                'outside'       => 'Both Sides — Center (outside)',
                                                'center-left'   => 'Both Left — Center',
                                                'center-right'  => 'Both Right — Center',
                                                'top-left'      => 'Both Top Left',
                                                'top-right'     => 'Both Top Right',
                                                'top-center'    => 'Both Top Center',
                                                'bottom-left'   => 'Both Bottom Left',
                                                'bottom-right'  => 'Both Bottom Right',
                                                'bottom-center' => 'Both Bottom Center',
                                            ])
                                            ->default('inside')
                                            ->helperText('Both arrows are grouped together at the chosen position'),
                                    ])
                                    ->visible(fn(Schemas\Components\Utilities\Get $get) => in_array($get('type'), ['hero_slider', 'products', 'reviews']))
                                    ->collapsible()
                                    ->collapsed(),

                                Schemas\Components\Section::make('Custom CSS / JS')
                                    ->schema([
                                        Forms\Components\Textarea::make('config.custom_css')
                                            ->label('Custom CSS')
                                            ->rows(4)
                                            ->placeholder('.my-section { background: #f5f5f5; }')
                                            ->helperText('CSS applied only to this section'),

                                        Forms\Components\Textarea::make('config.custom_js')
                                            ->label('Custom JS')
                                            ->rows(4)
                                            ->placeholder('console.log("section loaded");')
                                            ->helperText('JavaScript executed when this section renders'),
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

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'hero_slider' => 'primary',
                        'banner' => 'info',
                        'products' => 'success',
                        'reviews' => 'warning',
                        'custom_html' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => HomepageSection::TYPES[$state] ?? ucfirst($state)),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),

                Tables\Columns\TextColumn::make('config_summary')
                    ->label('Config')
                    ->getStateUsing(function (HomepageSection $record): string {
                        $config = $record->config ?? [];
                        $parts = [];

                        switch ($record->type) {
                            case 'hero_slider':
                                $count = count($config['slider_ids'] ?? []);
                                if ($count) $parts[] = "{$count} sliders";
                                break;
                            case 'banner':
                                $count = count($config['banner_ids'] ?? []);
                                if ($count) $parts[] = "{$count} banners";
                                if (!empty($config['cols'])) $parts[] = "{$config['cols']} cols";
                                break;
                            case 'products':
                                $count = count($config['product_ids'] ?? []);
                                if ($count) $parts[] = "{$count} products";
                                $mode = $config['display_mode'] ?? 'slider';
                                $parts[] = ucfirst($mode);
                                break;
                            case 'reviews':
                                $count = count($config['reviews'] ?? []);
                                if ($count) $parts[] = "{$count} reviews";
                                $parts[] = 'Slider';
                                break;
                            case 'custom_html':
                                $len = strlen($config['content'] ?? '');
                                if ($len) $parts[] = "{$len} chars";
                                break;
                        }

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
                    ->action(function (HomepageSection $record) {
                        $new = $record->replicate();
                        $new->sort_order = (HomepageSection::max('sort_order') ?? 0) + 1;
                        $new->name = $record->name . ' (Copy)';
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
            'index' => Pages\ListHomepageSections::route('/'),
            'create' => Pages\CreateHomepageSection::route('/create'),
            'edit' => Pages\EditHomepageSection::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('homepage_sections.view'));
    }
}

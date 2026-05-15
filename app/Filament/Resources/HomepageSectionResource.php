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
                        'custom_html' => 'warning',
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
                                if ($count)
                                    $parts[] = "{$count} sliders";
                                break;
                            case 'banner':
                                $count = count($config['banner_ids'] ?? []);
                                if ($count)
                                    $parts[] = "{$count} banners";
                                if (!empty($config['cols']))
                                    $parts[] = "{$config['cols']} cols";
                                break;
                            case 'products':
                                $count = count($config['product_ids'] ?? []);
                                if ($count)
                                    $parts[] = "{$count} products";
                                if (!empty($config['cols']))
                                    $parts[] = "{$config['cols']} cols";
                                break;
                            case 'custom_html':
                                $len = strlen($config['content'] ?? '');
                                if ($len)
                                    $parts[] = "{$len} chars";
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

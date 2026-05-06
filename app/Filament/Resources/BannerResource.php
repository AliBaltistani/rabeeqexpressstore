<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages;
use App\Models\Banner;
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

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.content');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.banners');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.banners');
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
                                Schemas\Components\Section::make('Banner Content')
                                    ->schema([
                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('title.en')
                                                    ->label('Title (English)')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('title.ar')
                                                    ->label('Title (Arabic)')
                                                    ->maxLength(255)
                                                    ->extraInputAttributes(['dir' => 'rtl']),
                                            ]),

                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('subtitle.en')
                                                    ->label('Subtitle (English)')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('subtitle.ar')
                                                    ->label('Subtitle (Arabic)')
                                                    ->maxLength(255)
                                                    ->extraInputAttributes(['dir' => 'rtl']),
                                            ]),

                                        Forms\Components\TextInput::make('link_url')
                                            ->label('Link URL')
                                            ->url()
                                            ->placeholder('https://...')
                                            ->maxLength(500),

                                        Forms\Components\Select::make('position')
                                            ->label('Position')
                                            ->options([
                                                'hero' => 'Hero Slider',
                                                'promo' => 'Promotional Banner (below hero)',
                                                'category_top' => 'Category Top Banner',
                                            ])
                                            ->required()
                                            ->default('hero'),
                                    ]),

                                Schemas\Components\Section::make('Images')
                                    ->schema([
                                        Forms\Components\FileUpload::make('image')
                                            ->label('Desktop Image')
                                            ->image()
                                            ->directory('banners')
                                            ->required()
                                            ->imageResizeMode('cover')
                                            ->imageResizeTargetWidth('1920')
                                            ->imageResizeTargetHeight('600')
                                            ->helperText('Recommended: 1920×600px'),
                                    ]),

                                Schemas\Components\Section::make('Schedule')
                                    ->schema([
                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\DateTimePicker::make('starts_at')
                                                    ->label('Start Date')
                                                    ->placeholder('Immediate')
                                                    ->native(false),

                                                Forms\Components\DateTimePicker::make('ends_at')
                                                    ->label('End Date')
                                                    ->placeholder('No end date')
                                                    ->after('starts_at')
                                                    ->native(false),
                                            ]),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ])
                            ->columnSpan(2),

                        // ── SIDEBAR (right 1/3) ──
                        Schemas\Components\Group::make()
                            ->schema([
                                Schemas\Components\Section::make('Settings')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Active')
                                            ->default(true)
                                            ->helperText('Show on the storefront'),

                                        Forms\Components\TextInput::make('sort_order')
                                            ->label('Sort Order')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0),
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
                    ->label('Preview')
                    ->width(120)
                    ->height(40),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->formatStateUsing(fn(Banner $record) => $record->getTranslation('title', 'en') ?: '—')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('title', 'like', "%{$search}%");
                    })
                    ->limit(40),

                Tables\Columns\TextColumn::make('position')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'hero' => 'primary',
                        'promo' => 'success',
                        'category_top' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'hero' => 'Hero Slider',
                        'promo' => 'Promo Banner',
                        'category_top' => 'Category Top',
                        default => ucfirst($state),
                    }),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('date_range')
                    ->label('Date Range')
                    ->getStateUsing(function (Banner $record): string {
                        if (!$record->starts_at && !$record->ends_at) return 'Always';
                        $start = $record->starts_at?->format('M d') ?? '—';
                        $end = $record->ends_at?->format('M d') ?? '∞';
                        return "{$start} → {$end}";
                    }),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('position')
                    ->options([
                        'hero' => 'Hero Slider',
                        'promo' => 'Promotional Banner',
                        'category_top' => 'Category Top',
                    ]),

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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit' => Pages\EditBanner::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('banners.view'));
    }
}

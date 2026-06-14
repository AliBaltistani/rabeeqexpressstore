<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CmsPageResource\Pages;
use App\Models\CmsPage;
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

class CmsPageResource extends Resource
{
    protected static ?string $model = CmsPage::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.content');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.cms_pages');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.cms_pages');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
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
                                Schemas\Components\Tabs::make('Page')
                                    ->tabs([
                                        // Tab: Content
                                        Schemas\Components\Tabs\Tab::make('Content')
                                            ->icon('heroicon-o-document-text')
                                            ->schema([
                                                Schemas\Components\Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('title.en')
                                                            ->label('Title (English)')
                                                            ->required()
                                                            ->maxLength(255),

                                                        Forms\Components\TextInput::make('title.ar')
                                                            ->label('Title (Arabic)')
                                                            ->maxLength(255)
                                                            ->extraInputAttributes(['dir' => 'rtl']),
                                                    ]),

                                                Forms\Components\TextInput::make('slug')
                                                    ->label('Slug')
                                                    ->unique(CmsPage::class, 'slug', ignoreRecord: true)
                                                    ->helperText('Auto-generated from title if left empty'),

                                                Schemas\Components\Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\Textarea::make('excerpt.en')
                                                            ->label('Excerpt (English)')
                                                            ->rows(2)
                                                            ->maxLength(500),

                                                        Forms\Components\Textarea::make('excerpt.ar')
                                                            ->label('Excerpt (Arabic)')
                                                            ->rows(2)
                                                            ->maxLength(500)
                                                            ->extraInputAttributes(['dir' => 'rtl']),
                                                    ]),

                                                Forms\Components\RichEditor::make('content.en')
                                                    ->label('Content (English)')
                                                    ->toolbarButtons([
                                                        'bold', 'italic', 'underline', 'strike',
                                                        'bulletList', 'orderedList',
                                                        'link', 'blockquote',
                                                        'h2', 'h3',
                                                        'undo', 'redo',
                                                    ]),

                                                Forms\Components\RichEditor::make('content.ar')
                                                    ->label('Content (Arabic)')
                                                    ->extraInputAttributes(['dir' => 'rtl'])
                                                    ->toolbarButtons([
                                                        'bold', 'italic', 'underline', 'strike',
                                                        'bulletList', 'orderedList',
                                                        'link', 'blockquote',
                                                        'h2', 'h3',
                                                        'undo', 'redo',
                                                    ]),
                                            ]),

                                        // Tab: Media
                                        Schemas\Components\Tabs\Tab::make('Media')
                                            ->icon('heroicon-o-photo')
                                            ->schema([
                                                Forms\Components\FileUpload::make('featured_image')
                                                    ->label('Featured Image')
                                                    ->image()
                                                    ->directory('cms-pages')
                                                    ->helperText('Primary image for this page'),
                                            ]),

                                        // Tab: Custom Code
                                        Schemas\Components\Tabs\Tab::make('Custom Code')
                                            ->icon('heroicon-o-code-bracket')
                                            ->schema([
                                                Forms\Components\Textarea::make('custom_css')
                                                    ->label('Custom CSS')
                                                    ->rows(6)
                                                    ->helperText('Additional CSS styles for this page'),
                                            ]),

                                        // Tab: SEO
                                        Schemas\Components\Tabs\Tab::make('SEO')
                                            ->icon('heroicon-o-magnifying-glass')
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
                                            ]),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(2),

                        // ── SIDEBAR (right 1/3) ──
                        Schemas\Components\Group::make()
                            ->schema([
                                Schemas\Components\Section::make('Settings')
                                    ->schema([
                                        Forms\Components\Select::make('template')
                                            ->options([
                                                'default' => 'Default',
                                                'legal' => 'Legal',
                                                'landing' => 'Landing',
                                            ])
                                            ->default('default')
                                            ->required(),

                                        Forms\Components\Select::make('status')
                                            ->options([
                                                'active' => 'Active',
                                                'inactive' => 'Inactive',
                                            ])
                                            ->default('active')
                                            ->required(),

                                        Forms\Components\TextInput::make('sort_order')
                                            ->label('Sort Order')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0),
                                    ]),

                                Schemas\Components\Section::make('Visibility')
                                    ->schema([
                                        Forms\Components\Toggle::make('show_in_header')
                                            ->label('Show in Header (Top Navbar)')
                                            ->default(false),

                                        Forms\Components\Toggle::make('show_in_footer')
                                            ->label('Show in Footer')
                                            ->default(false),
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
                Tables\Columns\ImageColumn::make('featured_image')
                    ->label('Image')
                    ->circular()
                    ->defaultImageUrl(fn() => 'https://ui-avatars.com/api/?name=P&background=8b5cf6&color=fff'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->formatStateUsing(fn(CmsPage $record) => $record->getTranslation('title', 'en'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('title', 'like', "%{$search}%");
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('template')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'default' => 'gray',
                        'legal' => 'info',
                        'landing' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
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
            'index' => Pages\ListCmsPages::route('/'),
            'create' => Pages\CreateCmsPage::route('/create'),
            'edit' => Pages\EditCmsPage::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('pages.view'));
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages;
use App\Models\Admin;
use App\Models\BlogCategory;
use App\Models\BlogPost;
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

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.content');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.blog_posts');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.blog_posts');
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
                                Schemas\Components\Tabs::make('Post')
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
                                                    ->unique(BlogPost::class, 'slug', ignoreRecord: true)
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

                                                Schemas\Components\Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\Select::make('category_id')
                                                            ->label('Category')
                                                            ->relationship('category', 'name')
                                                            ->getOptionLabelFromRecordUsing(fn(BlogCategory $record) => $record->getTranslation('name', 'en'))
                                                            ->searchable()
                                                            ->preload()
                                                            ->createOptionForm([
                                                                Forms\Components\TextInput::make('name.en')
                                                                    ->label('Name (EN)')
                                                                    ->required(),
                                                                Forms\Components\TextInput::make('name.ar')
                                                                    ->label('Name (AR)')
                                                                    ->extraInputAttributes(['dir' => 'rtl']),
                                                            ])
                                                            ->createOptionUsing(function (array $data): int {
                                                                $name = array_filter($data['name'] ?? []);
                                                                $cat = BlogCategory::create(['name' => $name]);
                                                                return $cat->id;
                                                            }),

                                                        Forms\Components\Select::make('admin_id')
                                                            ->label('Author')
                                                            ->relationship('admin', 'name')
                                                            ->default(fn() => auth()->guard('admin')->id())
                                                            ->searchable()
                                                            ->preload()
                                                            ->required(),
                                                    ]),
                                            ]),

                                        // Tab: Media
                                        Schemas\Components\Tabs\Tab::make('Media')
                                            ->icon('heroicon-o-photo')
                                            ->schema([
                                                Forms\Components\FileUpload::make('featured_image')
                                                    ->label('Featured Image')
                                                    ->image()
                                                    ->directory('blog')
                                                    ->imageResizeMode('cover')
                                                    ->imageResizeTargetWidth('1200')
                                                    ->imageResizeTargetHeight('630')
                                                    ->helperText('Recommended: 1200×630px for best display on social media'),
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

                                        // Tab: Settings
                                        Schemas\Components\Tabs\Tab::make('Settings')
                                            ->icon('heroicon-o-cog-6-tooth')
                                            ->schema([
                                                Forms\Components\Select::make('status')
                                                    ->options([
                                                        'draft' => 'Draft',
                                                        'published' => 'Published',
                                                    ])
                                                    ->default('draft')
                                                    ->required()
                                                    ->live(),

                                                Forms\Components\DateTimePicker::make('published_at')
                                                    ->label('Published At')
                                                    ->helperText('Auto-set when first published')
                                                    ->native(false),
                                            ]),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(2),

                        // ── SIDEBAR (right 1/3) ──
                        Schemas\Components\Group::make()
                            ->schema([
                                Schemas\Components\Section::make('Publish')
                                    ->schema([
                                        Forms\Components\Select::make('status')
                                            ->options([
                                                'draft' => 'Draft',
                                                'published' => 'Published',
                                            ])
                                            ->default('draft')
                                            ->required(),
                                    ]),

                                Schemas\Components\Section::make('Post Info')
                                    ->schema([
                                        Forms\Components\Placeholder::make('view_count_display')
                                            ->label('Views')
                                            ->content(fn(?BlogPost $record): string => $record ? number_format($record->view_count) : '0'),

                                        Forms\Components\Placeholder::make('created_info')
                                            ->label('Created')
                                            ->content(fn(?BlogPost $record): string => $record?->created_at?->format('M d, Y H:i') ?? 'Not saved yet'),
                                    ])
                                    ->hiddenOn('create'),
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
                    ->defaultImageUrl(fn() => 'https://ui-avatars.com/api/?name=Blog&background=6366f1&color=fff'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->formatStateUsing(fn(BlogPost $record) => $record->getTranslation('title', 'en'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('title', 'like', "%{$search}%");
                    })
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->formatStateUsing(fn($state, BlogPost $record) => $record->category?->getTranslation('name', 'en') ?? '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('admin.name')
                    ->label('Author')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime(admin_date_format())
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('view_count')
                    ->label('Views')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ]),

                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->getOptionLabelFromRecordUsing(fn(BlogCategory $record) => $record->getTranslation('name', 'en'))
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('admin_id')
                    ->label('Author')
                    ->relationship('admin', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('From'),
                        Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn(Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn(Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('publish')
                        ->label('Publish')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'status' => 'published',
                                    'published_at' => $record->published_at ?? now(),
                                ]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    Actions\BulkAction::make('unpublish')
                        ->label('Unpublish')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(fn($records) => $records->each->update(['status' => 'draft']))
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
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('blog.view'));
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SliderResource\Pages;
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

class SliderResource extends Resource
{
    protected static ?string $model = Slider::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-film';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.content');
    }

    public static function getNavigationLabel(): string
    {
        return 'Sliders';
    }

    public static function getModelLabel(): string
    {
        return 'Slider';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Sliders';
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
                                Schemas\Components\Section::make('Slider Content')
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

                                        Forms\Components\TextInput::make('link_url')
                                            ->label('Link URL')
                                            ->url()
                                            ->placeholder('https://...')
                                            ->maxLength(500),

                                        Forms\Components\FileUpload::make('image')
                                            ->label('Slider Image')
                                            ->image()
                                            ->directory('sliders')
                                            ->required()
                                            ->imageResizeMode('cover')
                                            ->imageResizeTargetWidth('1920')
                                            ->imageResizeTargetHeight('600')
                                            ->helperText('Recommended: 1920×600px'),
                                    ]),
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
                    ->formatStateUsing(fn(Slider $record) => $record->getTranslation('title', 'en') ?: '—')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('title', 'like', "%{$search}%");
                    })
                    ->limit(40),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
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
            'index' => Pages\ListSliders::route('/'),
            'create' => Pages\CreateSlider::route('/create'),
            'edit' => Pages\EditSlider::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('sliders.view'));
    }
}

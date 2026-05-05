<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TagResource\Pages;
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
use UnitEnum;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $form): Schema
    {
        return $form
            ->columns(1)
            ->schema([
                Schemas\Components\Section::make('Tag Information')
                    ->schema([
                        Forms\Components\TextInput::make('name.en')
                            ->label('Name (English)')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('name.ar')
                            ->label('Name (Arabic)')
                            ->extraInputAttributes(['dir' => 'rtl'])
                            ->maxLength(255),

                        Forms\Components\TextInput::make('slug')
                            ->unique(Tag::class, 'slug', ignoreRecord: true)
                            ->helperText('Auto-generated from name if left empty'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name (EN)')
                    ->formatStateUsing(fn(Tag $record) => $record->getTranslation('name', 'en'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('name', 'like', "%{$search}%");
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('name_ar')
                    ->label('Name (AR)')
                    ->state(fn(Tag $record) => $record->getTranslation('name', 'ar', false) ?: '—'),

                Tables\Columns\TextColumn::make('slug')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
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
            'index' => Pages\ListTags::route('/'),
            'create' => Pages\CreateTag::route('/create'),
            'edit' => Pages\EditTag::route('/{record}/edit'),
        ];
    }
}

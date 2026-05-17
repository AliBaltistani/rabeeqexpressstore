<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CountryResource\Pages;
use App\Models\Country;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-globe-alt';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.settings');
    }

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'الدول' : 'Countries';
    }

    public static function getModelLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'دولة' : 'Country';
    }

    public static function getPluralModelLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'الدول' : 'Countries';
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('name_en')
                            ->label('Name (English)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name_ar')
                            ->label('Name (Arabic)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->label('ISO 2 Code')
                            ->required()
                            ->maxLength(5)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('phone_code')
                            ->label('Phone Code (e.g. +971)')
                            ->maxLength(10),
                        Forms\Components\FileUpload::make('flag')
                            ->label('Flag Image')
                            ->image()
                            ->directory('flags')
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active for Usage')
                            ->default(true),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('flag')
                    ->label('Flag')
                    ->circular(),
                Tables\Columns\TextColumn::make('name_en')
                    ->label('Name (En)')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('Name (Ar)')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone_code')
                    ->label('Dial Code'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCountries::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FailedJobResource\Pages;
use App\Models\FailedJob;
use BackedEnum;
use UnitEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class FailedJobResource extends Resource
{
    protected static ?string $model = FailedJob::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-x-circle';
    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.system');
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('uuid'),
                Forms\Components\TextInput::make('connection'),
                Forms\Components\TextInput::make('queue'),
                Forms\Components\Textarea::make('payload')->columnSpanFull(),
                Forms\Components\Textarea::make('exception')->columnSpanFull(),
                Forms\Components\TextInput::make('failed_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('connection'),
                Tables\Columns\TextColumn::make('queue')->sortable(),
                Tables\Columns\TextColumn::make('exception')->limit(50),
                Tables\Columns\TextColumn::make('failed_at')->dateTime(),
            ])
            ->filters([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFailedJobs::route('/'),
        ];
    }
}

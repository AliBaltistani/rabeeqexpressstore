<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QueuedJobResource\Pages;
use App\Models\QueuedJob;
use BackedEnum;
use UnitEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class QueuedJobResource extends Resource
{
    protected static ?string $model = QueuedJob::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';
    
    protected static UnitEnum|string|null $navigationGroup = 'System';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('queue'),
                Forms\Components\Textarea::make('payload')->columnSpanFull(),
                Forms\Components\TextInput::make('attempts'),
                Forms\Components\TextInput::make('reserved_at'),
                Forms\Components\TextInput::make('available_at'),
                Forms\Components\TextInput::make('created_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('queue')->sortable(),
                Tables\Columns\TextColumn::make('payload')->limit(30),
                Tables\Columns\TextColumn::make('attempts'),
                Tables\Columns\TextColumn::make('available_at')->date(),
                Tables\Columns\TextColumn::make('created_at')->date(),
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
            'index' => Pages\ListQueuedJobs::route('/'),
        ];
    }
}

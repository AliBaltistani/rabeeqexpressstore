<?php

namespace App\Filament\Resources\QueuedJobs;

use App\Filament\Resources\QueuedJobs\Pages\CreateQueuedJob;
use App\Filament\Resources\QueuedJobs\Pages\EditQueuedJob;
use App\Filament\Resources\QueuedJobs\Pages\ListQueuedJobs;
use App\Filament\Resources\QueuedJobs\Pages\ViewQueuedJob;
use App\Filament\Resources\QueuedJobs\Schemas\QueuedJobForm;
use App\Filament\Resources\QueuedJobs\Schemas\QueuedJobInfolist;
use App\Filament\Resources\QueuedJobs\Tables\QueuedJobsTable;
use App\Models\QueuedJob;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class QueuedJobResource extends Resource
{
    protected static ?string $model = QueuedJob::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return QueuedJobForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return QueuedJobInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QueuedJobsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQueuedJobs::route('/'),
            'create' => CreateQueuedJob::route('/create'),
            'view' => ViewQueuedJob::route('/{record}'),
            'edit' => EditQueuedJob::route('/{record}/edit'),
        ];
    }
}

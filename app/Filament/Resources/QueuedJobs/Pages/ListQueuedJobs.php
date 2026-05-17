<?php

namespace App\Filament\Resources\QueuedJobs\Pages;

use App\Filament\Resources\QueuedJobs\QueuedJobResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQueuedJobs extends ListRecords
{
    protected static string $resource = QueuedJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

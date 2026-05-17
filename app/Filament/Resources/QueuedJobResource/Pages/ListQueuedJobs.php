<?php

namespace App\Filament\Resources\QueuedJobResource\Pages;

use App\Filament\Resources\QueuedJobResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQueuedJobs extends ListRecords
{
    protected static string $resource = QueuedJobResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

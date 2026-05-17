<?php

namespace App\Filament\Resources\QueuedJobs\Pages;

use App\Filament\Resources\QueuedJobs\QueuedJobResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewQueuedJob extends ViewRecord
{
    protected static string $resource = QueuedJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

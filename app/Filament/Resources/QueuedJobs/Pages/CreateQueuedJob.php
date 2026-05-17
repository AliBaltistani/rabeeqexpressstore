<?php

namespace App\Filament\Resources\QueuedJobs\Pages;

use App\Filament\Resources\QueuedJobs\QueuedJobResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQueuedJob extends CreateRecord
{
    protected static string $resource = QueuedJobResource::class;
}

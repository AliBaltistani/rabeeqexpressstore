<?php

namespace App\Filament\Resources\QueuedJobs\Schemas;

use Filament\Schemas\Schema;

class QueuedJobInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}

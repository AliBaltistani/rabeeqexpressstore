<?php

namespace App\Filament\Resources\FailedJobs\Schemas;

use Filament\Schemas\Schema;

class FailedJobInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}

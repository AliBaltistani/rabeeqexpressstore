<?php

namespace App\Filament\Resources\QueuedJobs\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class QueuedJobForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('queue')
                    ->required(),
                Textarea::make('payload')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('attempts')
                    ->required()
                    ->numeric(),
                TextInput::make('reserved_at')
                    ->numeric(),
                TextInput::make('available_at')
                    ->required()
                    ->numeric(),
            ]);
    }
}

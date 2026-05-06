<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('avatar'),
                Toggle::make('is_active')
                    ->required(),
                Toggle::make('is_banned')
                    ->required(),
                Textarea::make('ban_reason')
                    ->columnSpanFull(),
                TextInput::make('password')
                    ->password()
                    ->required(),
                TextInput::make('language_preference')
                    ->required()
                    ->default('en'),
                Toggle::make('is_rtl')
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
            ]);
    }
}

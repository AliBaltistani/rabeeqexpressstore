<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Infolists\Components\Section::make('Customer Info')
                    ->schema([
                        \Filament\Infolists\Components\Group::make([
                            \Filament\Infolists\Components\ImageEntry::make('avatar')
                                ->label('')
                                ->circular()
                                ->defaultImageUrl(fn(User $record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name))
                                ->size(80),
                            \Filament\Infolists\Components\TextEntry::make('name')
                                ->size(\Filament\Infolists\Components\TextEntry\TextEntrySize::Large)
                                ->weight(\Filament\Support\Enums\FontWeight::Bold),
                            \Filament\Infolists\Components\TextEntry::make('email')
                                ->icon('heroicon-m-envelope'),
                            \Filament\Infolists\Components\TextEntry::make('phone')
                                ->icon('heroicon-m-phone'),
                        ])->columnSpan(1),

                        \Filament\Infolists\Components\Group::make([
                            \Filament\Infolists\Components\Grid::make(3)->schema([
                                \Filament\Infolists\Components\TextEntry::make('orders_count')
                                    ->label('Total Orders')
                                    ->state(fn(User $record) => $record->orders()->count() ?: 0)
                                    ->size(\Filament\Infolists\Components\TextEntry\TextEntrySize::Large),
                                \Filament\Infolists\Components\TextEntry::make('total_spent')
                                    ->label('Total Spent')
                                    ->state(fn(User $record) => number_format($record->orders()->sum('total'), 2) . ' SAR')
                                    ->size(\Filament\Infolists\Components\TextEntry\TextEntrySize::Large),
                                \Filament\Infolists\Components\TextEntry::make('created_at')
                                    ->label('Member Since')
                                    ->date()
                                    ->size(\Filament\Infolists\Components\TextEntry\TextEntrySize::Large),
                            ]),
                        ])->columnSpan(2),
                    ])->columns(3),
            ]);
    }
}

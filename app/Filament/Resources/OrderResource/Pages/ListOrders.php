<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action — orders come from frontend only
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Orders')
                ->icon('heroicon-o-rectangle-stack'),

            'pending' => Tab::make('Pending')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'pending'))
                ->badge(fn() => \App\Models\Order::withoutGlobalScopes()->where('status', 'pending')->count()),

            'processing' => Tab::make('Processing')
                ->icon('heroicon-o-arrow-path')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'processing'))
                ->badge(fn() => \App\Models\Order::withoutGlobalScopes()->where('status', 'processing')->count()),

            'shipped' => Tab::make('Shipped')
                ->icon('heroicon-o-truck')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'shipped'))
                ->badge(fn() => \App\Models\Order::withoutGlobalScopes()->where('status', 'shipped')->count()),

            'delivered' => Tab::make('Delivered')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'delivered')),

            'cancelled' => Tab::make('Cancelled')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'cancelled')),
        ];
    }
}

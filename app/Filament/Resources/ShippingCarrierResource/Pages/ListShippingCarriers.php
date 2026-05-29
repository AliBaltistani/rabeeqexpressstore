<?php

namespace App\Filament\Resources\ShippingCarrierResource\Pages;

use App\Filament\Resources\ShippingCarrierResource;
use Filament\Resources\Pages\ListRecords;

class ListShippingCarriers extends ListRecords
{
    protected static string $resource = ShippingCarrierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}

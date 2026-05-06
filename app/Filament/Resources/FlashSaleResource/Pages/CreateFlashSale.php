<?php

namespace App\Filament\Resources\FlashSaleResource\Pages;

use App\Filament\Resources\FlashSaleResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateFlashSale extends CreateRecord
{
    protected static string $resource = FlashSaleResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Handle translatable fields
        if (isset($data['name']) && is_array($data['name'])) {
            $data['name'] = array_filter($data['name']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

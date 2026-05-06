<?php

namespace App\Filament\Resources\FlashSaleResource\Pages;

use App\Filament\Resources\FlashSaleResource;
use App\Models\FlashSale;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditFlashSale extends EditRecord
{
    protected static string $resource = FlashSaleResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        // Populate translatable fields
        $data['name'] = [
            'en' => $record->getTranslation('name', 'en', false),
            'ar' => $record->getTranslation('name', 'ar', false),
        ];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
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

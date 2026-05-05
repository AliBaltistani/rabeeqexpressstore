<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

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
        $data['description'] = [
            'en' => $record->getTranslation('description', 'en', false),
            'ar' => $record->getTranslation('description', 'ar', false),
        ];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['name']) && is_array($data['name'])) {
            $data['name'] = array_filter($data['name']);
        }
        if (isset($data['description']) && is_array($data['description'])) {
            $data['description'] = array_filter($data['description']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Handle translatable fields
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

<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Handle translatable fields
        foreach (['name', 'short_description', 'description'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = array_filter($data[$field]);
            }
        }

        // Remove dynamic_attributes from data (it's not a DB column)
        unset($data['dynamic_attributes']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncDynamicAttributes();
    }

    protected function syncDynamicAttributes(): void
    {
        $dynamicAttributes = $this->form->getState()['dynamic_attributes'] ?? [];
        $rawIds = collect($dynamicAttributes)->flatten()->filter()->map(fn($v) => (int) $v)->unique()->values()->all();

        // Guard: only sync IDs that actually exist in the DB to prevent FK violations
        $validIds = \App\Models\ProductAttributeValue::whereIn('id', $rawIds)->pluck('id')->all();

        $this->record->attributeValues()->sync($validIds);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}


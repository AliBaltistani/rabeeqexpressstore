<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

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
        foreach (['name', 'short_description', 'description'] as $field) {
            $data[$field] = [
                'en' => $record->getTranslation($field, 'en', false),
                'ar' => $record->getTranslation($field, 'ar', false),
            ];
        }

        // Populate dynamic_attributes from pivot
        $selectedValues = $record->attributeValues()->with('attribute')->get();
        $grouped = [];
        foreach ($selectedValues as $val) {
            $grouped[$val->attribute_id][] = (string) $val->id;
        }
        $data['dynamic_attributes'] = $grouped;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach (['name', 'short_description', 'description'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = array_filter($data[$field]);
            }
        }

        // Remove dynamic_attributes from data (it's not a DB column)
        unset($data['dynamic_attributes']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncDynamicAttributes();
    }

    protected function syncDynamicAttributes(): void
    {
        $dynamicAttributes = $this->form->getState()['dynamic_attributes'] ?? [];
        $valueIds = collect($dynamicAttributes)->flatten()->filter()->map(fn($v) => (int) $v)->unique()->values()->all();
        $this->record->attributeValues()->sync($valueIds);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}


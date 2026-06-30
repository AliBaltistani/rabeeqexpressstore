<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\ProductAttributeValue;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    /**
     * Store dynamic attributes before they are stripped from $data.
     * Same rationale as CreateProduct: calling $this->form->getState()
     * in afterSave() re-invokes the Repeater relationship save in Filament v5,
     * which overwrites the already-persisted images.
     */
    protected array $pendingDynamicAttributes = [];

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

        // Populate dynamic_attributes from pivot for pre-filling checkboxes
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
        // Capture dynamic_attributes BEFORE stripping them.
        $this->pendingDynamicAttributes = $data['dynamic_attributes'] ?? [];

        // Clean translatable fields
        foreach (['name', 'short_description', 'description'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = array_filter($data[$field]);
            }
        }

        // Remove non-DB keys
        unset($data['dynamic_attributes']);

        return $data;
    }

    protected function afterSave(): void
    {
        // Use the pre-captured property — NOT $this->form->getState()
        $this->syncDynamicAttributes($this->pendingDynamicAttributes);
    }

    protected function syncDynamicAttributes(array $dynamicAttributes): void
    {
        $rawIds = collect($dynamicAttributes)
            ->flatten()
            ->filter()
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        // Guard: only sync IDs that actually exist in the DB
        $validIds = ProductAttributeValue::whereIn('id', $rawIds)->pluck('id')->all();

        $this->record->attributeValues()->sync($validIds);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

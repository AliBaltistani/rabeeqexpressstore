<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\ProductAttributeValue;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    /**
     * Store dynamic attributes before they are stripped from $data.
     * We MUST capture them here because calling $this->form->getState()
     * in afterCreate() triggers a second Repeater relationship save in
     * Filament v5, which overwrites and corrupts already-saved images.
     */
    protected array $pendingDynamicAttributes = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Capture dynamic_attributes NOW, before we strip them.
        // afterCreate() will use this property instead of re-calling getState().
        $this->pendingDynamicAttributes = $data['dynamic_attributes'] ?? [];

        // Handle translatable fields — remove empty locale values
        foreach (['name', 'short_description', 'description'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = array_filter($data[$field]);
            }
        }

        // Remove non-DB keys so Product::create() doesn't receive them
        unset($data['dynamic_attributes']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Use the pre-captured property — never call $this->form->getState()
        // here because Filament v5 re-runs the Repeater relationship save
        // inside getState(), which overwrites the images that were just saved.
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

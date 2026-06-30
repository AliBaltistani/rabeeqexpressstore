<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\ProductAttributeValue;
use App\Models\ProductImage;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected array $pendingImages            = [];
    protected array $pendingDynamicAttributes = [];

    // -------------------------------------------------------------------
    // STEP 1 — Capture both images and attributes from validated $data
    // -------------------------------------------------------------------
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Capture — images are still livewire-file: temp references at this point.
        $this->pendingImages = array_values($data['images'] ?? []);
        
        $this->pendingDynamicAttributes = [];
        foreach ($data as $key => $val) {
            if (str_starts_with($key, 'dynamic_attributes_')) {
                $this->pendingDynamicAttributes[] = $val;
                unset($data[$key]);
            }
        }


        // Strip empty locale values from translatable fields
        foreach (['name', 'short_description', 'description'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = array_filter($data[$field]);
            }
        }

        // Strip non-fillable keys
        unset($data['images']);

        return $data;
    }

    // -------------------------------------------------------------------
    // STEP 2 — After Product is created, move files + create DB rows,
    //           then sync dynamic attributes
    // -------------------------------------------------------------------
    protected function afterCreate(): void
    {
        // 2a — Move Livewire temp files to permanent storage & create ProductImage rows
        try {
            $this->saveProductImages($this->pendingImages);
        } catch (Throwable $e) {
            Notification::make()
                ->title('Product created but images failed to save')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
            $this->pendingImages            = [];
            $this->pendingDynamicAttributes = [];
            return;
        }

        // 2b — Sync dynamic attributes
        try {
            $this->syncDynamicAttributes($this->pendingDynamicAttributes);
        } catch (Throwable $e) {
            Notification::make()
                ->title('Product created but attributes failed to save')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }

        $this->pendingImages            = [];
        $this->pendingDynamicAttributes = [];
    }

    // -------------------------------------------------------------------
    // Move livewire-file: temp references to permanent storage and create
    // ProductImage rows — one at a time (sequential).
    // -------------------------------------------------------------------
    protected function saveProductImages(array $images): void
    {
        foreach ($images as $item) {
            $rawPath = $item['image_path'] ?? null;
            if (empty($rawPath)) {
                continue;
            }

            if (str_starts_with($rawPath, 'livewire-file:')) {
                // Livewire v4 temp file — unserialize and move to permanent storage
                $tempFile = TemporaryUploadedFile::unserializeFromLivewireRequest($rawPath);

                if (! $tempFile instanceof TemporaryUploadedFile) {
                    continue;
                }

                $extension = $tempFile->guessExtension() ?? 'jpg';
                $filename  = Str::ulid() . '.' . $extension;

                // storeAs() moves the file from Livewire tmp → public disk products/
                $permanentPath = $tempFile->storeAs('products', $filename, ['disk' => 'public']);
            } else {
                // Already a permanent path (shouldn't happen on create, but guard anyway)
                $permanentPath = $rawPath;
            }

            ProductImage::create([
                'product_id' => $this->record->id,
                'image_path' => $permanentPath,
                'alt_text'   => $item['alt_text'] ?? null,
                'is_primary' => (bool) ($item['is_primary'] ?? false),
                'sort_order' => 0,
            ]);
        }
    }

    protected function syncDynamicAttributes(array $dynamicAttributes): void
    {
        if (empty($dynamicAttributes)) {
            return;
        }

        $rawIds = collect($dynamicAttributes)
            ->flatten()
            ->filter()
            ->map(function ($v) {
                $v = str_replace('id_', '', (string) $v);
                return (int) $v;
            })
            ->unique()
            ->values()
            ->all();

        $validIds = ProductAttributeValue::whereIn('id', $rawIds)->pluck('id')->all();
        $this->record->attributeValues()->sync($validIds);
    }

    // -------------------------------------------------------------------
    // Action labels
    // -------------------------------------------------------------------
    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Create Product')
            ->keyBindings(['mod+s']);
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Create & Add Another');
    }

    // -------------------------------------------------------------------
    // Success notification
    // -------------------------------------------------------------------
    protected function getCreatedNotification(): ?Notification
    {
        $raw  = $this->record?->name;
        $name = is_array($raw)
            ? ($raw['en'] ?? array_values($raw)[0] ?? 'Product')
            : ($raw ?? 'Product');

        return Notification::make()
            ->success()
            ->title('Product created')
            ->body('Product "' . $name . '" saved with images and attributes.');
    }

    // -------------------------------------------------------------------
    // Redirect
    // -------------------------------------------------------------------
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

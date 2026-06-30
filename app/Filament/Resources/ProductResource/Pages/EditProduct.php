<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\ProductAttributeValue;
use App\Models\ProductImage;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected array $pendingImages            = [];
    protected array $pendingDynamicAttributes = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // -------------------------------------------------------------------
    // Pre-fill: load existing translations and images (with IDs)
    // -------------------------------------------------------------------
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        foreach (['name', 'short_description', 'description'] as $field) {
            $data[$field] = [
                'en' => $record->getTranslation($field, 'en', false),
                'ar' => $record->getTranslation($field, 'ar', false),
            ];
        }

        // Load existing images WITH IDs — after sending these back through the form,
        // the Repeater state will include id so afterSave can update vs create.
        $data['images'] = $record->images()
            ->orderBy('sort_order')
            ->get(['id', 'image_path', 'alt_text', 'is_primary'])
            ->map(fn ($img) => [
                'id'         => $img->id,
                'image_path' => $img->image_path,  // permanent path
                'alt_text'   => $img->alt_text,
                'is_primary' => (bool) $img->is_primary,
            ])
            ->toArray();

        // Pre-fill dynamic attribute checkboxes
        $selectedValues = $record->attributeValues()->with('attribute')->get();
        foreach ($selectedValues as $val) {
            $data['dynamic_attributes_' . $val->attribute_id][] = 'id_' . $val->id;
        }

        return $data;
    }

    // -------------------------------------------------------------------
    // STEP 1 — Capture images + attributes from validated $data
    // -------------------------------------------------------------------
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingImages = array_values($data['images'] ?? []);
        
        $this->pendingDynamicAttributes = [];
        foreach ($data as $key => $val) {
            if (str_starts_with($key, 'dynamic_attributes_')) {
                $this->pendingDynamicAttributes[] = $val;
                unset($data[$key]);
            }
        }

        foreach (['name', 'short_description', 'description'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = array_filter($data[$field]);
            }
        }

        unset($data['images']);

        return $data;
    }

    // -------------------------------------------------------------------
    // STEP 2 — Sync images (move new temp files, delete removed) then attrs
    // -------------------------------------------------------------------
    protected function afterSave(): void
    {
        try {
            $this->syncProductImages($this->pendingImages);
        } catch (Throwable $e) {
            Notification::make()
                ->title('Product saved but images failed to update')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
            $this->pendingImages            = [];
            $this->pendingDynamicAttributes = [];
            return;
        }

        try {
            $this->syncDynamicAttributes($this->pendingDynamicAttributes);
        } catch (Throwable $e) {
            Notification::make()
                ->title('Product saved but attributes failed to update')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }

        $this->pendingImages            = [];
        $this->pendingDynamicAttributes = [];
    }

    // -------------------------------------------------------------------
    // Smart image sync:
    //   - existing items (have id, permanent path)  → update in place
    //   - new uploads (have livewire-file: path)    → move + create row
    //   - DB rows not in submitted list             → delete
    // -------------------------------------------------------------------
    protected function syncProductImages(array $images): void
    {
        $submittedIds = collect($images)
            ->pluck('id')
            ->filter()
            ->map(fn ($v) => (int) $v)
            ->all();

        // Delete removed images (and their physical files)
        $toDelete = $this->record->images()
            ->when($submittedIds, fn ($q) => $q->whereNotIn('id', $submittedIds))
            ->get();

        foreach ($toDelete as $img) {
            Storage::disk('public')->delete($img->image_path);
            $img->delete();
        }

        // Create or update
        foreach ($images as $item) {
            $rawPath = $item['image_path'] ?? null;
            if (empty($rawPath)) {
                continue;
            }

            if (str_starts_with($rawPath, 'livewire-file:')) {
                // New upload — move temp file to permanent storage
                $tempFile = TemporaryUploadedFile::unserializeFromLivewireRequest($rawPath);

                if (! $tempFile instanceof TemporaryUploadedFile) {
                    continue;
                }

                $extension    = $tempFile->guessExtension() ?? 'jpg';
                $filename     = Str::ulid() . '.' . $extension;
                $permanentPath = $tempFile->storeAs('products', $filename, ['disk' => 'public']);

                ProductImage::create([
                    'product_id' => $this->record->id,
                    'image_path' => $permanentPath,
                    'alt_text'   => $item['alt_text'] ?? null,
                    'is_primary' => (bool) ($item['is_primary'] ?? false),
                    'sort_order' => 0,
                ]);
            } elseif (!empty($item['id'])) {
                // Existing image — update metadata only (path unchanged)
                $this->record->images()->where('id', (int) $item['id'])->update([
                    'alt_text'   => $item['alt_text'] ?? null,
                    'is_primary' => (bool) ($item['is_primary'] ?? false),
                ]);
            }
        }
    }

    protected function syncDynamicAttributes(array $dynamicAttributes): void
    {
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
    // Success notification
    // -------------------------------------------------------------------
    protected function getSavedNotification(): ?Notification
    {
        $raw  = $this->record?->name;
        $name = is_array($raw)
            ? ($raw['en'] ?? array_values($raw)[0] ?? 'Product')
            : ($raw ?? 'Product');

        return Notification::make()
            ->success()
            ->title('Product updated')
            ->body('Product "' . $name . '" saved — images and attributes updated.');
    }

    // -------------------------------------------------------------------
    // Redirect
    // -------------------------------------------------------------------
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

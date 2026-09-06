<?php

namespace App\Filament\Imports;

use App\Models\Category;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CategoryImporter extends Importer
{
    protected static ?string $model = Category::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name_en')
                ->label('Name (EN)')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->example('Electronics'),

            ImportColumn::make('name_ar')
                ->label('Name (AR)')
                ->rules(['nullable', 'max:255'])
                ->example('إلكترونيات'),

            ImportColumn::make('slug')
                ->label('Slug')
                ->rules(['nullable', 'max:255'])
                ->example('electronics'),

            ImportColumn::make('parent_name')
                ->label('Parent Category (EN name)')
                ->rules(['nullable', 'max:255'])
                ->example(''),

            ImportColumn::make('description_en')
                ->label('Description (EN)')
                ->rules(['nullable'])
                ->example('All electronics and gadgets'),

            ImportColumn::make('description_ar')
                ->label('Description (AR)')
                ->rules(['nullable'])
                ->example('جميع الإلكترونيات والأجهزة'),

            ImportColumn::make('is_active')
                ->label('Active')
                ->boolean()
                ->rules(['nullable'])
                ->example('Yes'),

            ImportColumn::make('sort_order')
                ->label('Sort Order')
                ->numeric()
                ->rules(['nullable', 'integer', 'min:0'])
                ->example('0'),
        ];
    }

    public function resolveRecord(): ?Category
    {
        $nameEn = $this->data['name_en'] ?? null;

        if (!$nameEn) {
            return null;
        }

        // Try to find existing category by English name
        $existing = Category::withoutGlobalScope('active')
            ->where('name', 'like', '%"en":"' . addslashes($nameEn) . '"%')
            ->orWhere('name', 'like', '%"en": "' . addslashes($nameEn) . '"%')
            ->first();

        return $existing ?? new Category();
    }

    public function beforeFill(): void
    {
        // Resolve parent_name → parent_id
        $parentName = $this->data['parent_name'] ?? null;
        if ($parentName) {
            $parent = Category::withoutGlobalScope('active')
                ->where('name', 'like', '%"en":"' . addslashes($parentName) . '"%')
                ->orWhere('name', 'like', '%"en": "' . addslashes($parentName) . '"%')
                ->first();

            if ($parent) {
                $this->data['parent_id'] = $parent->id;
            }
        }
    }

    public function fillRecord(): void
    {
        $record = $this->record;

        // Slug
        if (!empty($this->data['slug'])) {
            $record->slug = $this->data['slug'];
        }

        // Parent
        if (isset($this->data['parent_id'])) {
            $record->parent_id = $this->data['parent_id'];
        }

        // Sort order
        $record->sort_order = $this->data['sort_order'] ?? $record->sort_order ?? 0;

        // Active
        if (isset($this->data['is_active'])) {
            $record->is_active = $this->data['is_active'];
        } elseif (!$record->exists) {
            $record->is_active = true;
        }

        // Translatable fields
        $translatableMap = [
            'name_en' => ['name', 'en'],
            'name_ar' => ['name', 'ar'],
            'description_en' => ['description', 'en'],
            'description_ar' => ['description', 'ar'],
        ];

        foreach ($translatableMap as $csvKey => [$field, $locale]) {
            $value = $this->data[$csvKey] ?? null;
            if ($value !== null && $value !== '') {
                $record->setTranslation($field, $locale, $value);
            }
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your category import has completed. ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}

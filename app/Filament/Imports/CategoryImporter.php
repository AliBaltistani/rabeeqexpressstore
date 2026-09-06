<?php

namespace App\Filament\Imports;

use App\Models\Category;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
                ->example('Unisex shoes'),

            ImportColumn::make('name_ar')
                ->label('Name (AR)')
                ->rules(['nullable', 'max:255'])
                ->example('أحذية للجنسين'),

            ImportColumn::make('slug')
                ->label('Slug (leave blank to auto-generate)')
                ->rules(['nullable', 'max:255'])
                ->example('unisex-shoes'),

            ImportColumn::make('parent_name')
                ->label('Parent Category (exact EN name, blank = root)')
                ->rules(['nullable', 'max:255'])
                ->example(''),

            ImportColumn::make('description_en')
                ->label('Description (EN)')
                ->rules(['nullable'])
                ->example('All unisex footwear styles'),

            ImportColumn::make('description_ar')
                ->label('Description (AR)')
                ->rules(['nullable'])
                ->example('جميع أنواع الأحذية'),

            ImportColumn::make('is_active')
                ->label('Active (Yes/No)')
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

    // ─── Record Resolution ────────────────────────────────────────────────────

    /**
     * Find existing category by EN name, or prepare a new one.
     * Uses PDO-safe LIKE + PHP fallback — no addslashes() so apostrophes work.
     */
    public function resolveRecord(): ?Category
    {
        $nameEn = trim($this->data['name_en'] ?? '');

        if ($nameEn === '') {
            return null;
        }

        // Try exact JSON match (PDO handles escaping — no addslashes needed)
        $existing = Category::withoutGlobalScopes()
            ->where('name', 'like', '%"en":"' . $nameEn . '"%')
            ->orWhere('name', 'like', '%"en": "' . $nameEn . '"%')
            ->first();

        if ($existing) {
            return $existing;
        }

        // PHP-side case-insensitive fallback for edge cases
        $existing = Category::withoutGlobalScopes()
            ->get()
            ->first(
                fn(Category $c) =>
                mb_strtolower($c->getTranslation('name', 'en', false) ?? '') === mb_strtolower($nameEn)
            );

        return $existing ?? new Category();
    }

    // ─── Validation (after Laravel field rules pass) ──────────────────────────

    public function afterValidate(): void
    {
        $errors = [];

        // ── Duplicate guard ────────────────────────────────────────────────
        // If a category with this English name already exists, reject the row.
        if ($this->record->exists) {
            throw ValidationException::withMessages([
                'name_en' => [
                    "Category \"{$this->record->getTranslation('name', 'en', false)}\" already exists "
                    . "(ID: {$this->record->id}). Remove this row or edit the category directly.",
                ],
            ]);
        }

        // ── Parent resolution ──────────────────────────────────────────────
        $parentName = trim($this->data['parent_name'] ?? '');
        if ($parentName !== '') {
            $parent = $this->resolveCategoryByName($parentName);
            if ($parent) {
                $this->data['_parent_id'] = $parent->id;
            } else {
                $errors['parent_name'] = [
                    "Parent category \"{$parentName}\" not found. "
                    . "Check the exact English name or leave blank for a root category.",
                ];
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function getValidationMessages(): array
    {
        return [
            'name_en.required' => 'English name is required.',
            'name_en.max' => 'English name must be 255 characters or fewer.',
            'sort_order.integer' => 'Sort order must be a whole number.',
            'sort_order.min' => 'Sort order cannot be negative.',
        ];
    }

    // ─── Fill Fields ──────────────────────────────────────────────────────────

    public function fillRecord(): void
    {
        $r = $this->record;
        $d = $this->data;

        // ── Parent (pre-resolved in afterValidate) ─────────────────────────
        if (isset($d['_parent_id'])) {
            $r->parent_id = $d['_parent_id'];
        }

        // ── Sort order ─────────────────────────────────────────────────────
        $r->sort_order = $d['sort_order'] ?? $r->sort_order ?? 0;

        // ── Active ─────────────────────────────────────────────────────────
        $r->is_active = $d['is_active'] ?? ($r->exists ? $r->is_active : true);

        // ── Slug (auto-generate from EN name if blank) ─────────────────────
        if (!empty($d['slug'])) {
            $r->slug = $d['slug'];
        } elseif (empty($r->slug)) {
            $base = Str::slug($d['name_en']);
            $slug = $base;
            $i = 1;
            while (Category::withoutGlobalScopes()->where('slug', $slug)->where('id', '!=', $r->id ?? 0)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $r->slug = $slug;
        }

        // ── Translatable fields ────────────────────────────────────────────
        $translations = [
            'name_en' => ['name', 'en'],
            'name_ar' => ['name', 'ar'],
            'description_en' => ['description', 'en'],
            'description_ar' => ['description', 'ar'],
        ];

        foreach ($translations as $col => [$field, $locale]) {
            $value = $d[$col] ?? null;
            if ($value !== null && $value !== '') {
                $r->setTranslation($field, $locale, $value);
            }
        }
    }

    // ─── Lookup Helper ────────────────────────────────────────────────────────

    /**
     * Find a category by English name.
     * No addslashes() — PDO binding handles escaping safely.
     */
    protected function resolveCategoryByName(string $name): ?Category
    {
        $found = Category::withoutGlobalScopes()
            ->where('name', 'like', '%"en":"' . $name . '"%')
            ->orWhere('name', 'like', '%"en": "' . $name . '"%')
            ->first();

        if ($found) {
            return $found;
        }

        return Category::withoutGlobalScopes()
            ->get()
            ->first(
                fn(Category $c) =>
                mb_strtolower($c->getTranslation('name', 'en', false) ?? '') === mb_strtolower($name)
            );
    }

    // ─── Completion Notification ──────────────────────────────────────────────

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your category import has completed. '
            . number_format($import->successful_rows) . ' '
            . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failed = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failed) . ' '
                . str('row')->plural($failed) . ' failed — download the failure report for details.';
        }

        return $body;
    }
}

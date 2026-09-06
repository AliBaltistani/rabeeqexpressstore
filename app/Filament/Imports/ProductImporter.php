<?php

namespace App\Filament\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('sku')
                ->label('SKU')
                ->requiredMapping()
                ->rules(['required', 'max:100'])
                ->example('PRD-NK001'),

            ImportColumn::make('name_en')
                ->label('Name (EN)')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->example('Nike Air Max 270'),

            ImportColumn::make('name_ar')
                ->label('Name (AR)')
                ->rules(['nullable', 'max:255'])
                ->example('نايك اير ماكس'),

            ImportColumn::make('category_name')
                ->label('Category (exact EN name)')
                ->rules(['nullable', 'max:255'])
                ->example('Unisex shoes'),

            ImportColumn::make('brand_name')
                ->label('Brand (exact name)')
                ->rules(['nullable', 'max:255'])
                ->example('Nike'),

            ImportColumn::make('product_type')
                ->label('Product Type')
                ->rules(['nullable', 'in:simple,variable'])
                ->example('simple'),

            ImportColumn::make('price')
                ->label('Price')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric', 'min:0'])
                ->example('99.99'),

            ImportColumn::make('compare_price')
                ->label('Compare Price')
                ->numeric()
                ->rules(['nullable', 'numeric', 'min:0'])
                ->example('129.99'),

            ImportColumn::make('cost_price')
                ->label('Cost Price')
                ->numeric()
                ->rules(['nullable', 'numeric', 'min:0'])
                ->example('45.00'),

            ImportColumn::make('stock_quantity')
                ->label('Stock Quantity')
                ->numeric()
                ->rules(['nullable', 'integer', 'min:0'])
                ->example('100'),

            ImportColumn::make('low_stock_threshold')
                ->label('Low Stock Threshold')
                ->numeric()
                ->rules(['nullable', 'integer', 'min:0'])
                ->example('5'),

            ImportColumn::make('track_stock')
                ->label('Track Stock')
                ->boolean()
                ->rules(['nullable'])
                ->example('Yes'),

            ImportColumn::make('allow_backorders')
                ->label('Allow Backorders')
                ->boolean()
                ->rules(['nullable'])
                ->example('No'),

            ImportColumn::make('weight')
                ->label('Weight (kg)')
                ->numeric()
                ->rules(['nullable', 'numeric', 'min:0'])
                ->example('0.35'),

            ImportColumn::make('sort_order')
                ->label('Sort Order')
                ->numeric()
                ->rules(['nullable', 'integer', 'min:0'])
                ->example('0'),

            ImportColumn::make('is_active')
                ->label('Active')
                ->boolean()
                ->rules(['nullable'])
                ->example('Yes'),

            ImportColumn::make('is_featured')
                ->label('Featured')
                ->boolean()
                ->rules(['nullable'])
                ->example('No'),

            ImportColumn::make('is_new')
                ->label('New')
                ->boolean()
                ->rules(['nullable'])
                ->example('Yes'),

            ImportColumn::make('short_description_en')
                ->label('Short Description (EN)')
                ->rules(['nullable', 'max:500'])
                ->example('Lightweight running shoes with Max Air unit.'),

            ImportColumn::make('short_description_ar')
                ->label('Short Description (AR)')
                ->rules(['nullable', 'max:500'])
                ->example('أحذية خفيفة الوزن مع وحدة Max Air.'),

            ImportColumn::make('description_en')
                ->label('Description (EN)')
                ->rules(['nullable'])
                ->example('Full product description in English.'),

            ImportColumn::make('description_ar')
                ->label('Description (AR)')
                ->rules(['nullable'])
                ->example('وصف كامل للمنتج بالعربية.'),

            ImportColumn::make('meta_title')
                ->label('Meta Title')
                ->rules(['nullable', 'max:60'])
                ->example('Buy Nike Air Max 270'),

            ImportColumn::make('meta_description')
                ->label('Meta Description')
                ->rules(['nullable', 'max:160'])
                ->example('Shop Nike Air Max 270 with free shipping.'),

            ImportColumn::make('meta_keywords')
                ->label('Meta Keywords')
                ->rules(['nullable', 'max:255'])
                ->example('nike, air max, running shoes'),

            ImportColumn::make('image_urls')
                ->label('Image URLs (pipe-separated)')
                ->rules(['nullable'])
                ->example('https://example.com/image1.jpg|https://example.com/image2.jpg'),

            ImportColumn::make('replace_images')
                ->label('Replace Existing Images (Yes/No)')
                ->boolean()
                ->rules(['nullable'])
                ->example('No'),
        ];
    }

    // ─── Record Resolution ────────────────────────────────────────────────────

    public function resolveRecord(): ?Product
    {
        $sku = trim($this->data['sku'] ?? '');

        if ($sku === '') {
            return null;
        }

        // Update existing or create new, bypassing active scope
        return Product::withoutGlobalScopes()->firstOrNew(['sku' => $sku]);
    }

    // ─── Validation (runs after Laravel field validation) ─────────────────────

    public function afterValidate(): void
    {
        $errors = [];

        // ── Duplicate SKU guard ────────────────────────────────────────────
        // If the record already exists in the DB, reject this row.
        // This prevents silent overwrites when the same CSV is uploaded again.
        if ($this->record->exists) {
            throw ValidationException::withMessages([
                'sku' => [
                    "Product with SKU \"{$this->record->sku}\" already exists (ID: {$this->record->id}). "
                    . 'Remove this row from the CSV, or edit the product directly in the admin panel.',
                ],
            ]);
        }

        // Resolve category by English name (Category.name is translatable JSON)
        $categoryName = trim($this->data['category_name'] ?? '');
        if ($categoryName !== '') {
            $category = $this->resolveCategoryByName($categoryName);
            if ($category) {
                $this->data['_category_id'] = $category->id;
            } else {
                $errors['category_name'] = [
                    "Category \"{$categoryName}\" not found. "
                    . "Check the exact English name or leave blank.",
                ];
            }
        }

        // Resolve brand by name (Brand.name is a plain string — NOT translatable JSON)
        $brandName = trim($this->data['brand_name'] ?? '');
        if ($brandName !== '') {
            $brand = $this->resolveBrandByName($brandName);
            if ($brand) {
                $this->data['_brand_id'] = $brand->id;
            } else {
                $errors['brand_name'] = [
                    "Brand \"{$brandName}\" not found. "
                    . "Check the exact brand name or leave blank.",
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
            'sku.required' => 'SKU is required.',
            'sku.max' => 'SKU must be 100 characters or fewer.',
            'name_en.required' => 'English name is required.',
            'name_en.max' => 'English name must be 255 characters or fewer.',
            'price.required' => 'Price is required.',
            'price.numeric' => 'Price must be a number (e.g. 99.99).',
            'price.min' => 'Price cannot be negative.',
            'compare_price.numeric' => 'Compare price must be a number.',
            'cost_price.numeric' => 'Cost price must be a number.',
            'stock_quantity.integer' => 'Stock quantity must be a whole number.',
            'stock_quantity.min' => 'Stock quantity cannot be negative.',
            'weight.numeric' => 'Weight must be a number.',
            'product_type.in' => 'Product type must be "simple" or "variable".',
            'meta_title.max' => 'Meta title must be 60 characters or fewer.',
            'meta_description.max' => 'Meta description must be 160 characters or fewer.',
        ];
    }

    // ─── Fill Fields ──────────────────────────────────────────────────────────

    public function fillRecord(): void
    {
        $r = $this->record;
        $d = $this->data;

        // ── Scalar fields ──────────────────────────────────────────────────
        $r->sku = trim($d['sku']);
        $r->price = $d['price'] ?? $r->price;
        $r->compare_price = isset($d['compare_price']) && $d['compare_price'] !== '' ? $d['compare_price'] : $r->compare_price;
        $r->cost_price = isset($d['cost_price']) && $d['cost_price'] !== '' ? $d['cost_price'] : $r->cost_price;
        $r->stock_quantity = $d['stock_quantity'] ?? $r->stock_quantity ?? 0;
        $r->low_stock_threshold = $d['low_stock_threshold'] ?? $r->low_stock_threshold ?? 5;
        $r->weight = isset($d['weight']) && $d['weight'] !== '' ? $d['weight'] : $r->weight;
        $r->sort_order = $d['sort_order'] ?? $r->sort_order ?? 0;
        $r->product_type = $d['product_type'] ?? $r->product_type ?? 'simple';
        $r->meta_title = $d['meta_title'] ?? $r->meta_title;
        $r->meta_description = $d['meta_description'] ?? $r->meta_description;
        $r->meta_keywords = $d['meta_keywords'] ?? $r->meta_keywords;

        // ── Relationships (pre-resolved in afterValidate) ──────────────────
        if (isset($d['_category_id'])) {
            $r->category_id = $d['_category_id'];
        }
        if (isset($d['_brand_id'])) {
            $r->brand_id = $d['_brand_id'];
        }

        // ── Booleans ───────────────────────────────────────────────────────
        $r->is_active = $d['is_active'] ?? ($r->exists ? $r->is_active : true);
        $r->is_featured = $d['is_featured'] ?? ($r->exists ? $r->is_featured : false);
        $r->is_new = $d['is_new'] ?? ($r->exists ? $r->is_new : false);
        $r->track_stock = $d['track_stock'] ?? ($r->exists ? $r->track_stock : true);
        $r->allow_backorders = $d['allow_backorders'] ?? ($r->exists ? $r->allow_backorders : false);

        // ── Slug (auto-generate if not set, Spatie handles uniqueness) ─────
        if (empty($r->slug)) {
            $base = Str::slug($d['name_en'] ?? $d['sku']);
            $slug = $base;
            $i = 1;
            while (Product::withoutGlobalScopes()->where('slug', $slug)->where('id', '!=', $r->id ?? 0)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $r->slug = $slug;
        }

        // ── Translatable fields (Spatie HasTranslations) ───────────────────
        $translations = [
            'name_en' => ['name', 'en'],
            'name_ar' => ['name', 'ar'],
            'short_description_en' => ['short_description', 'en'],
            'short_description_ar' => ['short_description', 'ar'],
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

    // ─── Image Handling (runs after save so product has an ID) ────────────────

    public function afterSave(): void
    {
        $urlsRaw = trim($this->data['image_urls'] ?? '');

        if ($urlsRaw === '') {
            return;
        }

        $urls = array_values(array_filter(array_map('trim', explode('|', $urlsRaw))));

        if (empty($urls)) {
            return;
        }

        $product = $this->record;
        $replaceImages = (bool) ($this->data['replace_images'] ?? false);

        // Delete old images if requested
        if ($replaceImages) {
            foreach ($product->images()->get() as $old) {
                Storage::disk('public')->delete($old->image_path);
            }
            $product->images()->delete();
        }

        $sortOrder = (int) ($product->images()->max('sort_order') ?? -1) + 1;
        $hasPrimary = $product->images()->where('is_primary', true)->exists();

        foreach ($urls as $index => $url) {
            try {
                $response = Http::withOptions(['verify' => false])
                    ->timeout(20)
                    ->get($url);

                if (!$response->successful()) {
                    Log::warning("ProductImporter: HTTP {$response->status()} for image URL: {$url}");
                    continue;
                }

                // Detect extension from Content-Type first, then URL path
                $contentType = strtolower($response->header('Content-Type') ?? '');
                $extension = match (true) {
                    str_contains($contentType, 'jpeg'),
                    str_contains($contentType, 'jpg') => 'jpg',
                    str_contains($contentType, 'png') => 'png',
                    str_contains($contentType, 'webp') => 'webp',
                    str_contains($contentType, 'gif') => 'gif',
                    default => ltrim(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION), '.') ?: 'jpg',
                };

                // Ensure products directory exists
                Storage::disk('public')->makeDirectory('products');

                $filename = 'products/' . Str::uuid() . '.' . $extension;
                Storage::disk('public')->put($filename, $response->body());

                $isPrimary = !$hasPrimary && $index === 0;

                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $filename,
                    'alt_text' => $product->getTranslation('name', 'en', false) ?? '',
                    'sort_order' => $sortOrder++,
                    'is_primary' => $isPrimary,
                ]);

                if ($isPrimary) {
                    $hasPrimary = true;
                }
            } catch (\Throwable $e) {
                // Log and skip — don't fail the entire row for a bad image URL
                Log::warning('ProductImporter: failed to download image', [
                    'product_id' => $product->id,
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    // ─── Lookup Helpers ───────────────────────────────────────────────────────

    /**
     * Find a Category by its English name.
     * Category.name is a Spatie translatable JSON column.
     * Two-pass: fast LIKE match first, then PHP-side case-insensitive fallback.
     */
    protected function resolveCategoryByName(string $name): ?Category
    {
        // PDO binds the value safely — do NOT use addslashes() here.
        // addslashes("Men' shoes") → "Men\' shoes" which breaks the JSON LIKE match
        // because MySQL stores {"en":"Men' shoes"} not {"en":"Men\' shoes"}.

        // Pass 1: exact JSON value match
        $found = Category::withoutGlobalScopes()
            ->where('name', 'like', '%"en":"' . $name . '"%')
            ->orWhere('name', 'like', '%"en": "' . $name . '"%')
            ->first();

        if ($found) {
            return $found;
        }

        // Pass 2: PHP-side case-insensitive fallback (handles all edge cases)
        return Category::withoutGlobalScopes()
            ->get()
            ->first(
                fn(Category $c) =>
                mb_strtolower($c->getTranslation('name', 'en', false) ?? '') === mb_strtolower($name)
            );
    }

    /**
     * Find a Brand by name.
     * IMPORTANT: Brand.name is a plain VARCHAR — NOT translatable JSON.
     * Never use JSON_EXTRACT / JSON_UNQUOTE on this column.
     */
    protected function resolveBrandByName(string $name): ?Brand
    {
        $lower = mb_strtolower($name);

        // Exact case-insensitive match first
        $found = Brand::whereRaw('LOWER(name) = ?', [$lower])->first();

        if ($found) {
            return $found;
        }

        // Partial LIKE match as fallback (e.g. "Nike Zoom" matches brand "Nike Zoom Vomero")
        return Brand::whereRaw('LOWER(name) LIKE ?', ['%' . $lower . '%'])->first();
    }

    // ─── Completion Notification ──────────────────────────────────────────────

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed. ' . number_format($import->successful_rows) . ' '
            . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' '
                . str('row')->plural($failedRowsCount) . ' failed — download the failure report for details.';
        }

        return $body;
    }
}

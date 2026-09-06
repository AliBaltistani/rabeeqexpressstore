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
use Illuminate\Support\Facades\Validator;
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
                ->example('PRD-ABC12345'),

            ImportColumn::make('name_en')
                ->label('Name (EN)')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->example('Premium Wireless Headphones'),

            ImportColumn::make('name_ar')
                ->label('Name (AR)')
                ->rules(['nullable', 'max:255'])
                ->example('سماعات لاسلكية'),

            ImportColumn::make('category_name')
                ->label('Category (EN name)')
                ->rules(['nullable', 'max:255'])
                ->example('Electronics'),

            ImportColumn::make('brand_name')
                ->label('Brand (EN name)')
                ->rules(['nullable', 'max:255'])
                ->example('Sony'),

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
                ->label('Weight')
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
                ->example('High-quality wireless headphones'),

            ImportColumn::make('short_description_ar')
                ->label('Short Description (AR)')
                ->rules(['nullable', 'max:500'])
                ->example('سماعات لاسلكية عالية الجودة'),

            ImportColumn::make('description_en')
                ->label('Description (EN)')
                ->rules(['nullable'])
                ->example('Full product description in English'),

            ImportColumn::make('description_ar')
                ->label('Description (AR)')
                ->rules(['nullable'])
                ->example('وصف كامل للمنتج بالعربية'),

            ImportColumn::make('meta_title')
                ->label('Meta Title')
                ->rules(['nullable', 'max:60'])
                ->example('Buy Premium Headphones'),

            ImportColumn::make('meta_description')
                ->label('Meta Description')
                ->rules(['nullable', 'max:160'])
                ->example('Shop the best wireless headphones'),

            ImportColumn::make('meta_keywords')
                ->label('Meta Keywords')
                ->rules(['nullable', 'max:255'])
                ->example('headphones, wireless, audio'),

            ImportColumn::make('image_urls')
                ->label('Image URLs (pipe-separated)')
                ->rules(['nullable'])
                ->example('https://example.com/image1.jpg|https://example.com/image2.jpg'),

            ImportColumn::make('replace_images')
                ->label('Replace Existing Images?')
                ->boolean()
                ->rules(['nullable'])
                ->example('No'),
        ];
    }

    public function resolveRecord(): ?Product
    {
        $sku = $this->data['sku'] ?? null;

        if (!$sku) {
            return null;
        }

        // Update existing or create new by SKU
        return Product::withoutGlobalScope('active')
            ->firstOrNew(['sku' => $sku]);
    }

    /**
     * Resolve category/brand names → IDs AFTER Laravel validation has passed.
     * If a name is provided but doesn't match any record, throw ValidationException
     * so this row lands in Filament's downloadable failure CSV with a clear reason.
     */
    public function afterValidate(): void
    {
        $errors = [];

        // ── Category resolution ────────────────────────────────────────────
        $categoryName = trim($this->data['category_name'] ?? '');
        if ($categoryName !== '') {
            $category = $this->findCategoryByName($categoryName);
            if ($category) {
                $this->data['_resolved_category_id'] = $category->id;
            } else {
                $errors['category_name'] = [
                    "Category '{$categoryName}' not found. Check spelling or create it first.",
                ];
            }
        }

        // ── Brand resolution ───────────────────────────────────────────────
        $brandName = trim($this->data['brand_name'] ?? '');
        if ($brandName !== '') {
            $brand = $this->findBrandByName($brandName);
            if ($brand) {
                $this->data['_resolved_brand_id'] = $brand->id;
            } else {
                $errors['brand_name'] = [
                    "Brand '{$brandName}' not found. Check spelling or create it first.",
                ];
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Find a category by English name — case-insensitive, trims whitespace.
     */
    protected function findCategoryByName(string $name): ?Category
    {
        // Try exact JSON match first (most common)
        $escaped = addslashes($name);
        $category = Category::withoutGlobalScope('active')
            ->where('name', 'like', '%"en":"' . $escaped . '"%')
            ->orWhere('name', 'like', '%"en": "' . $escaped . '"%')
            ->first();

        if ($category) {
            return $category;
        }

        // Fallback: case-insensitive search through all categories
        return Category::withoutGlobalScope('active')
            ->get()
            ->first(
                fn(Category $cat) =>
                mb_strtolower($cat->getTranslation('name', 'en', false) ?? '') === mb_strtolower($name)
            );
    }

    /**
     * Find a brand by name — Brand.name is a plain string (not translatable JSON).
     * Matches case-insensitively.
     */
    protected function findBrandByName(string $name): ?Brand
    {
        // Direct case-insensitive match — Brand.name is a plain string
        return Brand::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->orWhereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($name) . '%'])
            ->first();
    }

    public function getValidationMessages(): array
    {
        return [
            'sku.required' => 'SKU is required and cannot be empty.',
            'sku.max' => 'SKU must not exceed 100 characters.',
            'name_en.required' => 'English name is required.',
            'name_en.max' => 'English name must not exceed 255 characters.',
            'price.required' => 'Price is required.',
            'price.numeric' => 'Price must be a valid number (e.g. 99.99).',
            'price.min' => 'Price cannot be negative.',
            'compare_price.numeric' => 'Compare price must be a valid number.',
            'compare_price.min' => 'Compare price cannot be negative.',
            'cost_price.numeric' => 'Cost price must be a valid number.',
            'stock_quantity.integer' => 'Stock quantity must be a whole number.',
            'stock_quantity.min' => 'Stock quantity cannot be negative.',
            'weight.numeric' => 'Weight must be a valid number.',
            'product_type.in' => 'Product type must be either "simple" or "variable".',
            'meta_title.max' => 'Meta title must not exceed 60 characters.',
            'meta_description.max' => 'Meta description must not exceed 160 characters.',
        ];
    }

    public function fillRecord(): void
    {
        $record = $this->record;

        // Standard scalar fields
        $record->sku = $this->data['sku'];
        $record->price = $this->data['price'] ?? $record->price;
        $record->compare_price = $this->data['compare_price'] ?? $record->compare_price;
        $record->cost_price = $this->data['cost_price'] ?? $record->cost_price;
        $record->stock_quantity = $this->data['stock_quantity'] ?? $record->stock_quantity ?? 0;
        $record->low_stock_threshold = $this->data['low_stock_threshold'] ?? $record->low_stock_threshold ?? 5;
        $record->weight = $this->data['weight'] ?? $record->weight;
        $record->sort_order = $this->data['sort_order'] ?? $record->sort_order ?? 0;
        $record->product_type = $this->data['product_type'] ?? $record->product_type ?? 'simple';
        $record->meta_title = $this->data['meta_title'] ?? $record->meta_title;
        $record->meta_description = $this->data['meta_description'] ?? $record->meta_description;
        $record->meta_keywords = $this->data['meta_keywords'] ?? $record->meta_keywords;

        // Category & Brand — read pre-resolved IDs set in afterValidate()
        if (isset($this->data['_resolved_category_id'])) {
            $record->category_id = $this->data['_resolved_category_id'];
        }
        if (isset($this->data['_resolved_brand_id'])) {
            $record->brand_id = $this->data['_resolved_brand_id'];
        }

        // Booleans
        if (isset($this->data['is_active'])) {
            $record->is_active = $this->data['is_active'];
        } elseif (!$record->exists) {
            $record->is_active = true;
        }

        if (isset($this->data['is_featured'])) {
            $record->is_featured = $this->data['is_featured'];
        }

        if (isset($this->data['is_new'])) {
            $record->is_new = $this->data['is_new'];
        }

        if (isset($this->data['track_stock'])) {
            $record->track_stock = $this->data['track_stock'];
        } elseif (!$record->exists) {
            $record->track_stock = true;
        }

        if (isset($this->data['allow_backorders'])) {
            $record->allow_backorders = $this->data['allow_backorders'];
        }

        // Auto-generate slug from English name if not set
        // (Product requires a unique slug — Spatie HasSlug will handle uniqueness)
        if (empty($record->slug)) {
            $record->slug = Str::slug($this->data['name_en'] ?? $record->sku);
        }

        // Translatable fields — must use setTranslation()
        $translatableMap = [
            'name_en' => ['name', 'en'],
            'name_ar' => ['name', 'ar'],
            'short_description_en' => ['short_description', 'en'],
            'short_description_ar' => ['short_description', 'ar'],
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

    public function afterSave(): void
    {
        $urlsRaw = $this->data['image_urls'] ?? null;

        if (empty($urlsRaw)) {
            return;
        }

        $urls = array_filter(array_map('trim', explode('|', $urlsRaw)));

        if (empty($urls)) {
            return;
        }

        $product = $this->record;
        $replaceImages = (bool) ($this->data['replace_images'] ?? false);

        // Optionally delete old images
        if ($replaceImages) {
            foreach ($product->images as $oldImage) {
                Storage::disk('public')->delete($oldImage->image_path);
            }
            $product->images()->delete();
        }

        $existingSortMax = $product->images()->max('sort_order') ?? -1;
        $sortOrder = $existingSortMax + 1;
        $isPrimarySet = $product->images()->where('is_primary', true)->exists();

        foreach ($urls as $index => $url) {
            try {
                // Fetch image with a 15s timeout
                $response = Http::timeout(15)->get($url);

                if (!$response->successful()) {
                    continue;
                }

                $contentType = $response->header('Content-Type');
                $extension = match (true) {
                    str_contains($contentType, 'jpeg'), str_contains($contentType, 'jpg') => 'jpg',
                    str_contains($contentType, 'png') => 'png',
                    str_contains($contentType, 'webp') => 'webp',
                    str_contains($contentType, 'gif') => 'gif',
                    default => pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg',
                };

                $filename = 'products/' . Str::uuid() . '.' . $extension;
                Storage::disk('public')->put($filename, $response->body());

                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $filename,
                    'alt_text' => $product->getTranslation('name', 'en'),
                    'sort_order' => $sortOrder++,
                    'is_primary' => !$isPrimarySet && $index === 0,
                ]);

                if (!$isPrimarySet && $index === 0) {
                    $isPrimarySet = true;
                }
            } catch (\Throwable $e) {
                // Skip failed URL — don't break the entire import row
                \Illuminate\Support\Facades\Log::warning('ProductImporter: failed to download image', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed. ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}

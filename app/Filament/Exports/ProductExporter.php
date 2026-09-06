<?php

namespace App\Filament\Exports;

use App\Models\Product;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ProductExporter extends Exporter
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),

            ExportColumn::make('sku')
                ->label('SKU'),

            ExportColumn::make('name_en')
                ->label('Name (EN)')
                ->state(fn(Product $record): string => $record->getTranslation('name', 'en', false) ?? ''),

            ExportColumn::make('name_ar')
                ->label('Name (AR)')
                ->state(fn(Product $record): string => $record->getTranslation('name', 'ar', false) ?? ''),

            ExportColumn::make('slug')
                ->label('Slug'),

            ExportColumn::make('category_name')
                ->label('Category')
                ->state(
                    fn(Product $record): string =>
                    $record->category?->getTranslation('name', 'en', false) ?? ''
                ),

            ExportColumn::make('brand_name')
                ->label('Brand')
                // Brand.name is a plain VARCHAR — NOT translatable JSON.
                // Must use ->name directly, NOT ->getTranslation().
                ->state(fn(Product $record): string => $record->brand?->name ?? ''),

            ExportColumn::make('product_type')
                ->label('Product Type'),

            ExportColumn::make('price')
                ->label('Price'),

            ExportColumn::make('compare_price')
                ->label('Compare Price'),

            ExportColumn::make('cost_price')
                ->label('Cost Price'),

            ExportColumn::make('stock_quantity')
                ->label('Stock Quantity'),

            ExportColumn::make('low_stock_threshold')
                ->label('Low Stock Threshold'),

            ExportColumn::make('track_stock')
                ->label('Track Stock')
                ->state(fn(Product $record): string => $record->track_stock ? 'Yes' : 'No'),

            ExportColumn::make('allow_backorders')
                ->label('Allow Backorders')
                ->state(fn(Product $record): string => $record->allow_backorders ? 'Yes' : 'No'),

            ExportColumn::make('weight')
                ->label('Weight'),

            ExportColumn::make('sort_order')
                ->label('Sort Order'),

            ExportColumn::make('is_active')
                ->label('Active')
                ->state(fn(Product $record): string => $record->is_active ? 'Yes' : 'No'),

            ExportColumn::make('is_featured')
                ->label('Featured')
                ->state(fn(Product $record): string => $record->is_featured ? 'Yes' : 'No'),

            ExportColumn::make('is_new')
                ->label('New')
                ->state(fn(Product $record): string => $record->is_new ? 'Yes' : 'No'),

            ExportColumn::make('short_description_en')
                ->label('Short Description (EN)')
                ->state(
                    fn(Product $record): string =>
                    $record->getTranslation('short_description', 'en', false) ?? ''
                ),

            ExportColumn::make('short_description_ar')
                ->label('Short Description (AR)')
                ->state(
                    fn(Product $record): string =>
                    $record->getTranslation('short_description', 'ar', false) ?? ''
                ),

            ExportColumn::make('description_en')
                ->label('Description (EN)')
                ->state(
                    fn(Product $record): string =>
                    strip_tags($record->getTranslation('description', 'en', false) ?? '')
                ),

            ExportColumn::make('description_ar')
                ->label('Description (AR)')
                ->state(
                    fn(Product $record): string =>
                    strip_tags($record->getTranslation('description', 'ar', false) ?? '')
                ),

            ExportColumn::make('meta_title')
                ->label('Meta Title'),

            ExportColumn::make('meta_description')
                ->label('Meta Description'),

            ExportColumn::make('meta_keywords')
                ->label('Meta Keywords'),

            ExportColumn::make('image_urls')
                ->label('Image URLs (pipe-separated)')
                ->state(function (Product $record): string {
                    return $record->images
                        ->sortBy('sort_order')
                        ->map(fn($img) => url(Storage::url($img->image_path)))
                        ->implode('|');
                }),

            ExportColumn::make('replace_images')
                ->label('Replace Existing Images')
                ->state(fn(): string => 'No'),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        // withoutGlobalScopes() removes ALL global scopes (active, soft-delete, etc.)
        // so every product — active or inactive — is included in the export.
        return $query->withoutGlobalScopes()->with(['category', 'brand', 'images']);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your product export has completed. '
            . number_format($export->successful_rows) . ' '
            . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' '
                . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}

<?php

namespace App\Filament\Exports;

use App\Models\Category;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class CategoryExporter extends Exporter
{
    protected static ?string $model = Category::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),

            ExportColumn::make('name_en')
                ->label('Name (EN)')
                ->state(fn(Category $record): string => $record->getTranslation('name', 'en') ?? ''),

            ExportColumn::make('name_ar')
                ->label('Name (AR)')
                ->state(fn(Category $record): string => $record->getTranslation('name', 'ar') ?? ''),

            ExportColumn::make('slug')
                ->label('Slug'),

            ExportColumn::make('parent_name')
                ->label('Parent Category')
                ->state(fn(Category $record): string => $record->parent?->getTranslation('name', 'en') ?? ''),

            ExportColumn::make('description_en')
                ->label('Description (EN)')
                ->state(fn(Category $record): string => strip_tags($record->getTranslation('description', 'en') ?? '')),

            ExportColumn::make('description_ar')
                ->label('Description (AR)')
                ->state(fn(Category $record): string => strip_tags($record->getTranslation('description', 'ar') ?? '')),

            ExportColumn::make('is_active')
                ->label('Active')
                ->state(fn(Category $record): string => $record->is_active ? 'Yes' : 'No'),

            ExportColumn::make('sort_order')
                ->label('Sort Order'),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->withoutGlobalScope('active')->with('parent');
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your category export has completed. ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}

<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Exports\ProductExporter;
use App\Filament\Imports\ProductImporter;
use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(ProductImporter::class)
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->label('Import Products'),

            ExportAction::make()
                ->exporter(ProductExporter::class)
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->label('Export Products'),

            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Exports\CategoryExporter;
use App\Filament\Imports\CategoryImporter;
use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(CategoryImporter::class)
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->label('Import Categories'),

            ExportAction::make()
                ->exporter(CategoryExporter::class)
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->label('Export Categories'),

            Actions\CreateAction::make(),
        ];
    }
}

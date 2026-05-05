<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateBrand extends CreateRecord
{
    protected static string $resource = BrandResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

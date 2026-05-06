<?php
namespace App\Filament\Resources\BannerResource\Pages;
use App\Filament\Resources\BannerResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
class CreateBanner extends CreateRecord
{
    protected static string $resource = BannerResource::class;
    protected Width|string|null $maxContentWidth = Width::Full;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        foreach (['title', 'subtitle'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = array_filter($data[$field]);
            }
        }
        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

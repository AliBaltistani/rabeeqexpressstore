<?php
namespace App\Filament\Resources\CmsPageResource\Pages;
use App\Filament\Resources\CmsPageResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
class CreateCmsPage extends CreateRecord
{
    protected static string $resource = CmsPageResource::class;
    protected Width|string|null $maxContentWidth = Width::Full;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        foreach (['title', 'excerpt', 'content'] as $field) {
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

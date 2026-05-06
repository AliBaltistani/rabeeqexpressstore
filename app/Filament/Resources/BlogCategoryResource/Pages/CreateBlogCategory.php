<?php
namespace App\Filament\Resources\BlogCategoryResource\Pages;
use App\Filament\Resources\BlogCategoryResource;
use Filament\Resources\Pages\CreateRecord;
class CreateBlogCategory extends CreateRecord
{
    protected static string $resource = BlogCategoryResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['name']) && is_array($data['name'])) {
            $data['name'] = array_filter($data['name']);
        }
        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

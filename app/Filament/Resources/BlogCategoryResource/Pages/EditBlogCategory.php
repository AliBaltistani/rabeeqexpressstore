<?php
namespace App\Filament\Resources\BlogCategoryResource\Pages;
use App\Filament\Resources\BlogCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditBlogCategory extends EditRecord
{
    protected static string $resource = BlogCategoryResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $data['name'] = [
            'en' => $record->getTranslation('name', 'en', false),
            'ar' => $record->getTranslation('name', 'ar', false),
        ];
        return $data;
    }
    protected function mutateFormDataBeforeSave(array $data): array
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

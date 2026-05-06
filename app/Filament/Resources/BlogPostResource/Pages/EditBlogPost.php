<?php
namespace App\Filament\Resources\BlogPostResource\Pages;
use App\Filament\Resources\BlogPostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
class EditBlogPost extends EditRecord
{
    protected static string $resource = BlogPostResource::class;
    protected Width|string|null $maxContentWidth = Width::Full;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        foreach (['title', 'excerpt', 'content'] as $field) {
            $data[$field] = [
                'en' => $record->getTranslation($field, 'en', false),
                'ar' => $record->getTranslation($field, 'ar', false),
            ];
        }
        return $data;
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach (['title', 'excerpt', 'content'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = array_filter($data[$field]);
            }
        }
        if (($data['status'] ?? '') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }
        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

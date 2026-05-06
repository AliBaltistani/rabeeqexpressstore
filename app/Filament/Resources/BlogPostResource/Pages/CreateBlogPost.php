<?php
namespace App\Filament\Resources\BlogPostResource\Pages;
use App\Filament\Resources\BlogPostResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
class CreateBlogPost extends CreateRecord
{
    protected static string $resource = BlogPostResource::class;
    protected Width|string|null $maxContentWidth = Width::Full;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        foreach (['title', 'excerpt', 'content'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = array_filter($data[$field]);
            }
        }
        if (($data['status'] ?? '') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }
        if (empty($data['admin_id'])) {
            $data['admin_id'] = auth()->guard('admin')->id();
        }
        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

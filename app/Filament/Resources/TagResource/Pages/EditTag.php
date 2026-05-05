<?php

namespace App\Filament\Resources\TagResource\Pages;

use App\Filament\Resources\TagResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditTag extends EditRecord
{
    protected static string $resource = TagResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
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

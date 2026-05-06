<?php
namespace App\Filament\Resources\AdminUserResource\Pages;
use App\Filament\Resources\AdminUserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditAdminUser extends EditRecord
{
    protected static string $resource = AdminUserResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->visible(fn() => $this->getRecord()->id !== auth()->guard('admin')->id())];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

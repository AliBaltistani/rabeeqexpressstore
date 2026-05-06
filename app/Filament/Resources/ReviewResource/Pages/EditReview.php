<?php

namespace App\Filament\Resources\ReviewResource\Pages;

use App\Filament\Resources\ReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReview extends EditRecord
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),

            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn(): bool => $this->getRecord()->status !== 'approved')
                ->action(function (): void {
                    $this->getRecord()->update(['status' => 'approved']);
                    $this->refreshFormData(['status']);
                })
                ->requiresConfirmation(),

            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn(): bool => $this->getRecord()->status !== 'rejected')
                ->action(function (): void {
                    $this->getRecord()->update(['status' => 'rejected']);
                    $this->refreshFormData(['status']);
                })
                ->requiresConfirmation(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

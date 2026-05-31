<?php

namespace App\Filament\Resources\LoyaltyRewardResource\Pages;

use App\Filament\Resources\LoyaltyRewardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditLoyaltyReward extends EditRecord
{
    protected static string $resource = LoyaltyRewardResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

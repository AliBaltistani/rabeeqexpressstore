<?php

namespace App\Filament\Resources\LoyaltyRewardResource\Pages;

use App\Filament\Resources\LoyaltyRewardResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateLoyaltyReward extends CreateRecord
{
    protected static string $resource = LoyaltyRewardResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

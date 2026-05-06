<?php

namespace App\Filament\Resources\CouponResource\Pages;

use App\Filament\Resources\CouponResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateCoupon extends CreateRecord
{
    protected static string $resource = CouponResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

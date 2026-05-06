<?php

namespace App\Filament\Pages\Settings;

use App\Models\Currency;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CurrenciesPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.settings.currencies-page';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';
    protected static string|UnitEnum|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Currencies';
    protected static ?string $title = 'Currencies';

    protected function getTableQuery(): Builder
    {
        return Currency::query();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')->sortable(),
            Tables\Columns\TextColumn::make('code')->badge()->color('primary'),
            Tables\Columns\TextColumn::make('symbol'),
            Tables\Columns\TextColumn::make('exchange_rate')->label('Rate vs SAR')->numeric(6),
            Tables\Columns\TextColumn::make('decimal_places')->label('Decimals'),
            Tables\Columns\IconColumn::make('is_default')->boolean()->label('Default'),
            Tables\Columns\ToggleColumn::make('is_active')->label('Active'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Actions\EditAction::make()
                ->form(self::getCurrencyFormSchema()),

            Actions\Action::make('set_default')
                ->label('Set Default')
                ->icon('heroicon-o-star')
                ->color('warning')
                ->visible(fn(Currency $record) => !$record->is_default)
                ->action(function (Currency $record) {
                    Currency::where('is_default', true)->update(['is_default' => false]);
                    $record->update(['is_default' => true, 'is_active' => true]);
                    Notification::make()->title('Default currency updated')->success()->send();
                })
                ->requiresConfirmation(),

            Actions\DeleteAction::make()
                ->visible(fn(Currency $record) => !$record->is_default),
        ];
    }

    protected function getTableHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->model(Currency::class)
                ->label('Add Currency')
                ->form(self::getCurrencyFormSchema()),
        ];
    }

    private static function getCurrencyFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('code')->required()->maxLength(3),
            Forms\Components\TextInput::make('symbol')->required()->maxLength(10),
            Forms\Components\TextInput::make('exchange_rate')->label('Exchange Rate vs SAR')->numeric()->step(0.000001)->required()->default(1),
            Forms\Components\TextInput::make('decimal_places')->numeric()->default(2)->minValue(0)->maxValue(4),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('settings.view'));
    }
}

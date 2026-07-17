<?php

namespace App\Filament\Pages\Settings;

use App\Models\Currency;
use App\Models\Setting;
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
    protected static ?int $navigationSort = 3;

    // ─── Store Currency form state ───
    public string $storeCurrencyCode = 'SAR';

    public function mount(): void
    {
        $this->storeCurrencyCode = store_currency_code();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.settings_pages.currencies');
    }

    public function getTitle(): string
    {
        return __('admin.settings_pages.currencies');
    }

    /**
     * Save the Store Currency setting (the currency prices are stored in).
     * This is a destructive action — warns the admin before saving.
     */
    public function saveStoreCurrency(): void
    {
        Setting::set('general.store_currency', $this->storeCurrencyCode);

        Notification::make()
            ->title('Store Currency Updated')
            ->body("All price conversions now treat {$this->storeCurrencyCode} as the base. Make sure your product prices are entered in {$this->storeCurrencyCode}.")
            ->warning()
            ->send();
    }

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
            Tables\Columns\TextColumn::make('exchange_rate')
                ->label(fn() => 'Rate vs ' . store_currency_code())
                ->numeric(6),
            Tables\Columns\TextColumn::make('decimal_places')->label('Decimals'),
            Tables\Columns\IconColumn::make('is_default')->boolean()->label('Display Default'),
            Tables\Columns\ToggleColumn::make('is_active')->label('Active'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Actions\EditAction::make()
                ->form(self::getCurrencyFormSchema()),

            Actions\Action::make('set_display_default')
                ->label('Set as Display Default')
                ->icon('heroicon-o-star')
                ->color('warning')
                ->visible(fn(Currency $record) => !$record->is_default)
                ->action(function (Currency $record) {
                    Currency::where('is_default', true)->update(['is_default' => false]);
                    $record->update(['is_default' => true, 'is_active' => true]);
                    Notification::make()
                        ->title('Display default updated')
                        ->body("{$record->name} ({$record->code}) is now the default currency shown to new visitors. Products are still priced in " . store_currency_code() . '.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Set Display Default Currency')
                ->modalDescription(fn(Currency $record) =>
                    "New visitors will see prices in {$record->name} ({$record->code}). " .
                    "All prices will be converted from " . store_currency_code() . " using the exchange rate. " .
                    "This does NOT change the store base currency."
                ),

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
            Forms\Components\TextInput::make('code')->required()->maxLength(3)
                ->helperText('Enter 2–3 letter ISO code in UPPERCASE, e.g. AED, USD, KWD')
                ->rules(['regex:/^[A-Z]{2,3}$/']),
            Forms\Components\TextInput::make('symbol')->required()->maxLength(10),
            Forms\Components\TextInput::make('exchange_rate')
                ->label(fn() => 'Exchange Rate vs ' . store_currency_code())
                ->helperText(fn() => 'How many units of this currency equal 1 ' . store_currency_code() . '. Example: if 1 SAR = 3.67 AED, enter 3.67 for AED.')
                ->numeric()
                ->step(0.000001)
                ->required()
                ->default(1),
            Forms\Components\TextInput::make('decimal_places')
                ->numeric()
                ->default(2)
                ->minValue(0)
                ->maxValue(4),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('settings.view'));
    }
}

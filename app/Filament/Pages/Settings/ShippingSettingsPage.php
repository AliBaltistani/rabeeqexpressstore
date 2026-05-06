<?php

namespace App\Filament\Pages\Settings;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components;
use UnitEnum;

class ShippingSettingsPage extends Page
{
    protected string $view = 'filament.pages.settings.general-settings';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';
    protected static string|UnitEnum|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Shipping';
    protected static ?string $title = 'Shipping Settings';

    public ?array $data = [];

    public function mount(): void
    {
        $fields = ['free_shipping_threshold', 'default_weight_unit', 'enable_shipping_calculator', 'require_phone'];
        foreach ($fields as $key) {
            $this->data[$key] = Setting::get("shipping.{$key}");
        }
        $this->form->fill($this->data);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Components\Section::make('General Shipping Settings')
                    ->schema([
                        Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('free_shipping_threshold')
                                ->label('Free Shipping Threshold (SAR)')
                                ->numeric()->default(0)
                                ->helperText('0 = disabled. Orders above this amount get free shipping.'),
                            Forms\Components\Select::make('default_weight_unit')
                                ->label('Default Weight Unit')
                                ->options(['kg' => 'Kilograms (kg)', 'lb' => 'Pounds (lb)'])
                                ->default('kg'),
                        ]),
                        Components\Grid::make(2)->schema([
                            Forms\Components\Toggle::make('enable_shipping_calculator')
                                ->label('Enable Shipping Calculator on product page')
                                ->default(false),
                            Forms\Components\Toggle::make('require_phone')
                                ->label('Require phone number for delivery')
                                ->default(true),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        foreach ($data as $key => $value) {
            Setting::set("shipping.{$key}", $value);
        }
        Notification::make()->title('Shipping Settings Saved')->success()->send();
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('settings.view'));
    }
}

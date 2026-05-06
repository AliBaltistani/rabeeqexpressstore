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

class PaymentGatewaysPage extends Page
{
    protected string $view = 'filament.pages.settings.general-settings';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';
    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.settings_pages.payment');
    }

    public function getTitle(): string
    {
        return __('admin.settings_pages.payment');
    }

    public ?array $data = [];

    public function mount(): void
    {
        $fields = [
            'stripe_enabled', 'stripe_publishable_key', 'stripe_secret_key', 'stripe_webhook_secret', 'stripe_mode',
            'paypal_enabled', 'paypal_client_id', 'paypal_client_secret', 'paypal_mode',
            'cod_enabled', 'cod_label_en', 'cod_label_ar', 'cod_description_en', 'cod_description_ar', 'cod_extra_fee',
            'bank_enabled', 'bank_label_en', 'bank_label_ar', 'bank_name', 'bank_account_name', 'bank_iban', 'bank_swift', 'bank_instructions_en', 'bank_instructions_ar',
        ];
        foreach ($fields as $key) {
            $value = Setting::get("payment.{$key}");
            if (in_array($key, ['stripe_secret_key', 'stripe_webhook_secret', 'paypal_client_secret'])) {
                $this->data[$key] = $value ? decrypt($value) : null;
            } else {
                $this->data[$key] = $value;
            }
        }
        $this->form->fill($this->data);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                // Stripe
                Components\Section::make('Stripe')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Forms\Components\Toggle::make('stripe_enabled')->label('Enable Stripe'),
                        Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('stripe_publishable_key')->label('Publishable Key'),
                            Forms\Components\TextInput::make('stripe_secret_key')->label('Secret Key')->password()->revealable(),
                        ]),
                        Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('stripe_webhook_secret')->label('Webhook Secret')->password()->revealable(),
                            Forms\Components\Select::make('stripe_mode')->label('Mode')->options(['test' => 'Test', 'live' => 'Live'])->default('test'),
                        ]),
                    ])->collapsible(),

                // PayPal
                Components\Section::make('PayPal')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\Toggle::make('paypal_enabled')->label('Enable PayPal'),
                        Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('paypal_client_id')->label('Client ID'),
                            Forms\Components\TextInput::make('paypal_client_secret')->label('Client Secret')->password()->revealable(),
                        ]),
                        Forms\Components\Select::make('paypal_mode')->label('Mode')->options(['sandbox' => 'Sandbox', 'live' => 'Live'])->default('sandbox'),
                    ])->collapsible(),

                // Cash on Delivery
                Components\Section::make('Cash on Delivery')
                    ->icon('heroicon-o-truck')
                    ->schema([
                        Forms\Components\Toggle::make('cod_enabled')->label('Enable COD'),
                        Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('cod_label_en')->label('Label (English)')->default('Cash on Delivery'),
                            Forms\Components\TextInput::make('cod_label_ar')->label('Label (Arabic)')->default('الدفع عند الاستلام')->extraInputAttributes(['dir' => 'rtl']),
                        ]),
                        Components\Grid::make(2)->schema([
                            Forms\Components\Textarea::make('cod_description_en')->label('Description (English)')->rows(2),
                            Forms\Components\Textarea::make('cod_description_ar')->label('Description (Arabic)')->rows(2)->extraInputAttributes(['dir' => 'rtl']),
                        ]),
                        Forms\Components\TextInput::make('cod_extra_fee')->label('Extra Fee (' . currency_symbol() . ')')->numeric()->default(0)->helperText('0 = no extra fee'),
                    ])->collapsible(),

                // Bank Transfer
                Components\Section::make('Direct Bank Transfer')
                    ->icon('heroicon-o-building-library')
                    ->schema([
                        Forms\Components\Toggle::make('bank_enabled')->label('Enable Bank Transfer'),
                        Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('bank_label_en')->label('Label (English)'),
                            Forms\Components\TextInput::make('bank_label_ar')->label('Label (Arabic)')->extraInputAttributes(['dir' => 'rtl']),
                        ]),
                        Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('bank_name')->label('Bank Name'),
                            Forms\Components\TextInput::make('bank_account_name')->label('Account Name'),
                        ]),
                        Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('bank_iban')->label('IBAN'),
                            Forms\Components\TextInput::make('bank_swift')->label('SWIFT/BIC'),
                        ]),
                        Components\Grid::make(2)->schema([
                            Forms\Components\Textarea::make('bank_instructions_en')->label('Instructions (English)')->rows(2),
                            Forms\Components\Textarea::make('bank_instructions_ar')->label('Instructions (Arabic)')->rows(2)->extraInputAttributes(['dir' => 'rtl']),
                        ]),
                    ])->collapsible(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $encrypted = ['stripe_secret_key', 'stripe_webhook_secret', 'paypal_client_secret'];

        foreach ($data as $key => $value) {
            if (in_array($key, $encrypted) && $value) {
                $value = encrypt($value);
            }
            Setting::set("payment.{$key}", $value);
        }

        Notification::make()->title('Payment Settings Saved')->success()->send();
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('settings.view'));
    }
}

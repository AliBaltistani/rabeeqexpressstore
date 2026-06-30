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
        return __('admin.settings_pages.payment') . ' & Shipping';
    }

    public function getTitle(): string
    {
        return __('admin.settings_pages.payment') . ' & Shipping';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $fields = [
            // Stripe
            'stripe_enabled', 'stripe_publishable_key', 'stripe_secret_key', 'stripe_webhook_secret', 'stripe_mode',
            // PayPal
            'paypal_enabled', 'paypal_client_id', 'paypal_client_secret', 'paypal_mode',
            // COD
            'cod_enabled', 'cod_label_en', 'cod_label_ar', 'cod_description_en', 'cod_description_ar', 'cod_extra_fee',
            // Bank Transfer
            'bank_enabled', 'bank_label_en', 'bank_label_ar', 'bank_name', 'bank_account_name', 'bank_iban', 'bank_swift', 'bank_instructions_en', 'bank_instructions_ar',
        ];

        // Shipping fields (stored under 'shipping.' prefix)
        $shippingFields = [
            'smsa_enabled', 'smsa_pass_key', 'smsa_wsdl_url',
            'default_method', 'free_shipping_threshold',
        ];

        foreach ($fields as $key) {
            $value = Setting::get("payment.{$key}");
            if (in_array($key, ['stripe_secret_key', 'stripe_webhook_secret', 'paypal_client_secret'])) {
                $this->data[$key] = $value ? decrypt($value) : null;
            } else {
                $this->data[$key] = $value;
            }
        }

        foreach ($shippingFields as $key) {
            $this->data["shipping_{$key}"] = Setting::get("shipping.{$key}");
        }

        // Sensible defaults for shipping
        $this->data['shipping_smsa_enabled'] ??= false;
        $this->data['shipping_smsa_wsdl_url'] ??= 'https://track.smsaexpress.com/SELOAPI/ServiceSELO.svc?wsdl';
        $this->data['shipping_default_method'] ??= 'standard';
        $this->data['shipping_free_shipping_threshold'] ??= 0;

        $this->form->fill($this->data);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                // ═══════════════════════════════════════
                // PAYMENT GATEWAYS
                // ═══════════════════════════════════════

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
                            Forms\Components\TextInput::make('stripe_webhook_secret')->label('Webhook Secret')->password()->revealable()
                                ->helperText('Get this from Stripe Dashboard → Developers → Webhooks → your endpoint → Signing secret.'),
                            Forms\Components\Select::make('stripe_mode')->label('Mode')->options(['test' => 'Test', 'live' => 'Live'])->default('test'),
                        ]),
                        Forms\Components\Placeholder::make('stripe_webhook_url')
                            ->label('Webhook Endpoint URL')
                            ->content(fn () => url('/stripe/webhook'))
                            ->helperText('Copy this URL into Stripe Dashboard → Developers → Webhooks → Add endpoint. Enable events: payment_intent.succeeded, payment_intent.payment_failed, charge.refunded.'),
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

                // ═══════════════════════════════════════
                // SHIPPING CONFIGURATION
                // ═══════════════════════════════════════

                Components\Section::make('Shipping Configuration')
                    ->icon('heroicon-o-globe-alt')
                    ->schema([
                        Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('shipping_default_method')
                                ->label('Default Shipping Method')
                                ->options(function () {
                                    return \App\Models\ShippingMethod::where('is_active', true)
                                        ->orderBy('sort_order')
                                        ->get()
                                        ->mapWithKeys(function (\App\Models\ShippingMethod $m) {
                                            return [$m->slug => $m->getTranslation('name', 'en')];
                                        })
                                        ->toArray();
                                })
                                ->searchable()
                                ->placeholder('Select a shipping method'),
                            Forms\Components\TextInput::make('shipping_free_shipping_threshold')
                                ->label('Free Shipping Threshold (' . currency_symbol() . ')')
                                ->numeric()
                                ->default(0)
                                ->helperText('Minimum order total for free shipping (0 = disabled)'),
                        ]),
                    ])->collapsible(),

                // SMSA Express
                Components\Section::make('SMSA Express Integration')
                    ->icon('heroicon-o-paper-airplane')
                    ->schema([
                        Forms\Components\Toggle::make('shipping_smsa_enabled')
                            ->label('Enable SMSA Express')
                            ->helperText('Use SMSA SOAP API for shipment booking and tracking')
                            ->default(false),
                        Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('shipping_smsa_pass_key')
                                ->label('SMSA Pass Key')
                                ->password()
                                ->revealable()
                                ->placeholder('Your SMSA API passkey'),
                            Forms\Components\TextInput::make('shipping_smsa_wsdl_url')
                                ->label('SMSA WSDL URL')
                                ->url()
                                ->default('https://track.smsaexpress.com/SELOAPI/ServiceSELO.svc?wsdl')
                                ->helperText('SMSA SOAP API endpoint'),
                        ]),
                    ])->collapsible(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $encrypted = ['stripe_secret_key', 'stripe_webhook_secret', 'paypal_client_secret'];

        // Shipping fields use 'shipping.' prefix
        $shippingKeys = ['shipping_smsa_enabled', 'shipping_smsa_pass_key', 'shipping_smsa_wsdl_url', 'shipping_default_method', 'shipping_free_shipping_threshold'];

        foreach ($data as $key => $value) {
            if (in_array($key, $shippingKeys)) {
                // Save under shipping.* prefix
                $settingKey = 'shipping.' . str_replace('shipping_', '', $key);
                Setting::set($settingKey, $value);
            } else {
                // Save under payment.* prefix
                if (in_array($key, $encrypted) && $value) {
                    $value = encrypt($value);
                }
                Setting::set("payment.{$key}", $value);
            }
        }

        Notification::make()->title('Payment & Shipping Settings Saved')->success()->send();
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('settings.view'));
    }
}

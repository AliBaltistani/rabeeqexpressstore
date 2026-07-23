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

class LoyaltyWalletSettingsPage extends Page
{
    protected string $view = 'filament.pages.settings.general-settings';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-gift';
    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.settings');
    }

    public static function getNavigationLabel(): string
    {
        return 'Loyalty & Wallet';
    }

    public function getTitle(): string
    {
        return 'Loyalty & Wallet Settings';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $loyaltyKeys = ['enabled', 'earn_rate', 'redeem_rate', 'min_redeem', 'profile_completion_points', 'share_points', 'store_url', 'coupon_expiry_days', 'points_expiry_days'];
        $walletKeys  = ['enabled', 'auto_refund'];

        foreach ($loyaltyKeys as $key) {
            $this->data["loyalty_{$key}"] = Setting::get("loyalty.{$key}");
        }

        foreach ($walletKeys as $key) {
            $this->data["wallet_{$key}"] = Setting::get("wallet.{$key}");
        }

        // Cast booleans
        $this->data['loyalty_enabled'] = (bool) ($this->data['loyalty_enabled'] ?? false);
        $this->data['wallet_enabled']  = (bool) ($this->data['wallet_enabled'] ?? false);
        $this->data['wallet_auto_refund'] = (bool) ($this->data['wallet_auto_refund'] ?? false);

        // Cast numbers
        $this->data['loyalty_earn_rate']  = (float) ($this->data['loyalty_earn_rate'] ?? 1);
        $this->data['loyalty_redeem_rate'] = (float) ($this->data['loyalty_redeem_rate'] ?? 100);
        $this->data['loyalty_min_redeem'] = (int) ($this->data['loyalty_min_redeem'] ?? 100);
        $this->data['loyalty_profile_completion_points'] = (int) ($this->data['loyalty_profile_completion_points'] ?? 0);
        $this->data['loyalty_share_points'] = (int) ($this->data['loyalty_share_points'] ?? 50);
        $this->data['loyalty_store_url'] = $this->data['loyalty_store_url'] ?? config('app.url');
        $this->data['loyalty_coupon_expiry_days'] = (int) ($this->data['loyalty_coupon_expiry_days'] ?? 30);
        $this->data['loyalty_points_expiry_days'] = (int) ($this->data['loyalty_points_expiry_days'] ?? 0);

        $this->form->fill($this->data);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                // ═══ LOYALTY POINTS ═══
                Components\Section::make('Loyalty Points Program')
                    ->icon('heroicon-o-gift')
                    ->description('Configure how customers earn and redeem loyalty points on orders.')
                    ->schema([
                        Forms\Components\Toggle::make('loyalty_enabled')
                            ->label('Enable Loyalty Program')
                            ->helperText('When enabled, customers earn points on delivered orders.')
                            ->default(false),

                        Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('loyalty_earn_rate')
                                ->label('Earn Rate (points per ' . store_currency_symbol() . ')')
                                ->numeric()
                                ->minValue(0)
                                ->step(0.1)
                                ->default(1)
                                ->helperText('e.g. 1 = earn 1 point for every 1 ' . currency_code() . ' spent'),

                            Forms\Components\TextInput::make('loyalty_redeem_rate')
                                ->label('Redeem Rate (points per ' . store_currency_symbol() . ')')
                                ->numeric()
                                ->minValue(1)
                                ->default(100)
                                ->helperText('e.g. 100 = 100 points converts to 1 ' . currency_code()),

                            Forms\Components\TextInput::make('loyalty_min_redeem')
                                ->label('Min Points to Redeem')
                                ->numeric()
                                ->minValue(1)
                                ->default(100)
                                ->helperText('Minimum points required before a customer can redeem.'),

                            Forms\Components\TextInput::make('loyalty_profile_completion_points')
                                ->label('Profile Completion Points')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->helperText('Points awarded to users who complete their profile (one-time). Set 0 to disable.'),

                            Forms\Components\TextInput::make('loyalty_share_points')
                                ->label('Share Store Points')
                                ->numeric()
                                ->minValue(0)
                                ->default(50)
                                ->helperText('Points awarded when a customer shares the store link.'),

                            Forms\Components\TextInput::make('loyalty_store_url')
                                ->label('Store URL (for sharing)')
                                ->url()
                                ->placeholder(config('app.url'))
                                ->helperText('The URL shown to customers for sharing.'),

                            Forms\Components\TextInput::make('loyalty_coupon_expiry_days')
                                ->label('Coupon Expiry (days)')
                                ->numeric()
                                ->minValue(1)
                                ->default(30)
                                ->helperText('How many days loyalty-generated coupons remain valid. Default: 30.'),

                            Forms\Components\TextInput::make('loyalty_points_expiry_days')
                                ->label('Points Expiry (days)')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->helperText('Days until earned points expire. Set 0 to disable. Requires scheduled "loyalty:expire-points" command.'),
                        ]),
                    ])->collapsible(),

                // ═══ WALLET ═══
                Components\Section::make('Wallet Payments')
                    ->icon('heroicon-o-wallet')
                    ->description('Allow customers to pay for orders using their wallet balance.')
                    ->schema([
                        Forms\Components\Toggle::make('wallet_enabled')
                            ->label('Enable Wallet Payments')
                            ->helperText('Customers will see "Pay with Wallet" as a payment option at checkout.')
                            ->default(false),

                        Forms\Components\Toggle::make('wallet_auto_refund')
                            ->label('Auto-Credit Refunds to Wallet')
                            ->helperText('When an order is refunded, automatically credit the amount to the customer\'s wallet instead of the original payment method.')
                            ->default(false),
                    ])->collapsible(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $loyaltyKeys = ['enabled', 'earn_rate', 'redeem_rate', 'min_redeem', 'profile_completion_points', 'share_points', 'store_url', 'coupon_expiry_days', 'points_expiry_days'];
        $walletKeys  = ['enabled', 'auto_refund'];

        foreach ($loyaltyKeys as $key) {
            Setting::set("loyalty.{$key}", $data["loyalty_{$key}"] ?? null);
        }

        foreach ($walletKeys as $key) {
            Setting::set("wallet.{$key}", $data["wallet_{$key}"] ?? null);
        }

        Notification::make()->title('Loyalty & Wallet Settings Saved')->success()->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->submit('save'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('settings.view'));
    }
}

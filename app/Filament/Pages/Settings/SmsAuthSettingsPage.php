<?php

namespace App\Filament\Pages\Settings;

use App\Models\Setting;
use App\Services\TwilioService;
use BackedEnum;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components;
use UnitEnum;

class SmsAuthSettingsPage extends Page
{
    protected string $view = 'filament.pages.settings.general-settings';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static ?int $navigationSort = 7;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.settings');
    }

    public static function getNavigationLabel(): string
    {
        return 'SMS & Auth Settings';
    }

    public function getTitle(): string
    {
        return 'SMS & Auth Settings';
    }

    public ?array $data = [];

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('sendTestSms')
                ->label('Send Test SMS')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('primary')
                ->form([
                    Forms\Components\TextInput::make('phone')
                        ->label('Phone Number')
                        ->placeholder('+966501234567')
                        ->required(),
                ])
                ->action(function (array $data) {
                    try {
                        app(TwilioService::class)->sendSms(
                            $data['phone'],
                            'Test SMS from ' . config('app.name') . '. Your Twilio integration is working!'
                        );
                        Notification::make()
                            ->title('Test SMS sent successfully!')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to send SMS')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function mount(): void
    {
        $fields = [
            // Auth / OTP method
            'otp_mode', 'email_otp_enabled', 'phone_otp_enabled',
            // Twilio credentials
            'account_sid', 'auth_token', 'from_number', 'messaging_service_sid',
            // SMS notifications
            'notify_order_status', 'notify_welcome', 'notify_order_shipped',
        ];

        foreach ($fields as $key) {
            // Auth settings live under 'auth.*', Twilio under 'twilio.*', SMS under 'sms.*'
            $group = match(true) {
                in_array($key, ['otp_mode', 'email_otp_enabled', 'phone_otp_enabled']) => 'auth',
                in_array($key, ['account_sid', 'auth_token', 'from_number', 'messaging_service_sid']) => 'twilio',
                default => 'sms',
            };
            $this->data[$key] = Setting::get("{$group}.{$key}");
        }

        $this->form->fill($this->data);
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([

            Components\Section::make('OTP / Authentication Method')->schema([
                Forms\Components\Select::make('otp_mode')
                    ->label('Default OTP Method')
                    ->options([
                        'email' => 'Email OTP only',
                        'phone' => 'Phone (SMS) OTP only',
                        'both'  => 'Both — user chooses',
                    ])
                    ->default('email')
                    ->required()
                    ->helperText('Controls how login & registration OTP is delivered.'),
                Components\Grid::make(2)->schema([
                    Forms\Components\Toggle::make('email_otp_enabled')
                        ->label('Enable Email OTP')
                        ->default(true),
                    Forms\Components\Toggle::make('phone_otp_enabled')
                        ->label('Enable Phone OTP (SMS)')
                        ->default(false),
                ]),
            ]),

            Components\Section::make('Twilio Credentials')->schema([
                Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('account_sid')
                        ->label('Account SID')
                        ->password()
                        ->revealable()
                        ->helperText('Starts with AC…'),
                    Forms\Components\TextInput::make('auth_token')
                        ->label('Auth Token')
                        ->password()
                        ->revealable(),
                ]),
                Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('from_number')
                        ->label('From Number')
                        ->placeholder('+15551234567')
                        ->helperText('Your Twilio phone number in E.164 format.'),
                    Forms\Components\TextInput::make('messaging_service_sid')
                        ->label('Messaging Service SID')
                        ->placeholder('MG…')
                        ->helperText('Optional. Overrides From Number if set.'),
                ]),
            ]),

            Components\Section::make('SMS Notifications')->schema([
                Components\Grid::make(3)->schema([
                    Forms\Components\Toggle::make('notify_order_status')
                        ->label('Order Status Changed')
                        ->default(false),
                    Forms\Components\Toggle::make('notify_order_shipped')
                        ->label('Order Shipped')
                        ->default(false),
                    Forms\Components\Toggle::make('notify_welcome')
                        ->label('Welcome SMS')
                        ->default(false),
                ]),
            ]),

        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Auth settings
        Setting::set('auth.otp_mode', $data['otp_mode']);
        Setting::set('auth.email_otp_enabled', $data['email_otp_enabled']);
        Setting::set('auth.phone_otp_enabled', $data['phone_otp_enabled']);

        // Twilio credentials
        Setting::set('twilio.account_sid', $data['account_sid']);
        Setting::set('twilio.auth_token', $data['auth_token']);
        Setting::set('twilio.from_number', $data['from_number']);
        Setting::set('twilio.messaging_service_sid', $data['messaging_service_sid']);

        // SMS notifications
        Setting::set('sms.notify_order_status', $data['notify_order_status']);
        Setting::set('sms.notify_order_shipped', $data['notify_order_shipped']);
        Setting::set('sms.notify_welcome', $data['notify_welcome']);

        Notification::make()->title('SMS & Auth Settings Saved')->success()->send();
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('settings.view'));
    }
}

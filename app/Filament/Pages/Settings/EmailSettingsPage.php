<?php

namespace App\Filament\Pages\Settings;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components;
use UnitEnum;

class EmailSettingsPage extends Page
{
    protected string $view = 'filament.pages.settings.general-settings';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';
    protected static string|UnitEnum|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationLabel = 'Email / Notifications';
    protected static ?string $title = 'Email & Notification Settings';

    public ?array $data = [];

    public function mount(): void
    {
        $fields = [
            'mailer', 'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption',
            'from_name', 'from_email',
            'notify_order_placed', 'notify_status_changed', 'notify_order_shipped',
            'notify_order_delivered', 'notify_order_cancelled', 'notify_order_refunded',
            'notify_password_reset', 'notify_welcome', 'notify_review_approved',
            'admin_email', 'admin_notify_new_order', 'admin_notify_low_stock',
            'admin_notify_new_customer', 'admin_notify_new_review',
        ];
        foreach ($fields as $key) {
            $this->data[$key] = Setting::get("email.{$key}");
        }
        $this->form->fill($this->data);
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Components\Section::make('Mail Configuration')->schema([
                Components\Grid::make(2)->schema([
                    Forms\Components\Select::make('mailer')->options([
                        'smtp' => 'SMTP', 'mailgun' => 'Mailgun', 'ses' => 'SES',
                    ])->default('smtp'),
                    Forms\Components\TextInput::make('from_email')->label('From Email')->email(),
                ]),
                Forms\Components\TextInput::make('from_name')->label('From Name'),
                Components\Grid::make(4)->schema([
                    Forms\Components\TextInput::make('smtp_host')->label('Host'),
                    Forms\Components\TextInput::make('smtp_port')->label('Port')->numeric(),
                    Forms\Components\TextInput::make('smtp_username')->label('Username'),
                    Forms\Components\TextInput::make('smtp_password')->label('Password')->password()->revealable(),
                ]),
                Forms\Components\Select::make('smtp_encryption')->options(['tls' => 'TLS', 'ssl' => 'SSL'])->default('tls'),
            ]),
            Components\Section::make('Customer Notifications')->schema([
                Components\Grid::make(2)->schema([
                    Forms\Components\Toggle::make('notify_order_placed')->label('Order Placed')->default(true),
                    Forms\Components\Toggle::make('notify_status_changed')->label('Status Changed')->default(true),
                    Forms\Components\Toggle::make('notify_order_shipped')->label('Order Shipped')->default(true),
                    Forms\Components\Toggle::make('notify_order_delivered')->label('Order Delivered')->default(true),
                    Forms\Components\Toggle::make('notify_order_cancelled')->label('Order Cancelled')->default(true),
                    Forms\Components\Toggle::make('notify_order_refunded')->label('Order Refunded')->default(true),
                    Forms\Components\Toggle::make('notify_password_reset')->label('Password Reset')->default(true),
                    Forms\Components\Toggle::make('notify_welcome')->label('Welcome Email')->default(true),
                    Forms\Components\Toggle::make('notify_review_approved')->label('Review Approved')->default(false),
                ]),
            ]),
            Components\Section::make('Admin Notifications')->schema([
                Forms\Components\TextInput::make('admin_email')->label('Admin email(s)')->helperText('Comma-separated'),
                Components\Grid::make(2)->schema([
                    Forms\Components\Toggle::make('admin_notify_new_order')->label('New Order')->default(true),
                    Forms\Components\Toggle::make('admin_notify_low_stock')->label('Low Stock')->default(true),
                    Forms\Components\Toggle::make('admin_notify_new_customer')->label('New Customer')->default(false),
                    Forms\Components\Toggle::make('admin_notify_new_review')->label('New Review')->default(false),
                ]),
            ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        foreach ($data as $key => $value) {
            Setting::set("email.{$key}", $value);
        }
        Notification::make()->title('Email Settings Saved')->success()->send();
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('settings.view'));
    }
}

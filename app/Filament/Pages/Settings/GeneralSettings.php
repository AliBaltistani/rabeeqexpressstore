<?php

namespace App\Filament\Pages\Settings;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components;
use BackedEnum;
use UnitEnum;

class GeneralSettings extends Page
{
    protected string $view = 'filament.pages.settings.general-settings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'General Settings';

    protected static ?string $title = 'General Settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::getGroup('general');
        $this->data = [];

        $fields = [
            'general.store_name_en', 'general.store_name_ar', 'general.store_tagline_en', 'general.store_tagline_ar',
            'general.store_logo', 'general.store_favicon', 'general.store_email', 'general.store_phone',
            'general.store_whatsapp', 'general.store_address_en', 'general.store_address_ar',
            'general.default_language', 'general.default_currency', 'general.timezone', 'general.date_format',
            'general.products_per_page', 'general.enable_guest_checkout', 'general.enable_wishlist',
            'general.enable_reviews', 'general.reviews_require_approval', 'general.show_out_of_stock',
            'general.low_stock_threshold',
            'general.maintenance_mode', 'general.maintenance_message_en', 'general.maintenance_message_ar',
        ];

        foreach ($fields as $field) {
            $key = str_replace('general.', '', $field);
            $this->data[$key] = Setting::get($field);
        }

        $this->form->fill($this->data);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                // Store Information
                Components\Section::make('Store Information')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('store_name_en')
                                    ->label('Store Name (English)')
                                    ->required(),
                                Forms\Components\TextInput::make('store_name_ar')
                                    ->label('Store Name (Arabic)')
                                    ->required()
                                    ->extraInputAttributes(['dir' => 'rtl']),
                            ]),

                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('store_tagline_en')
                                    ->label('Store Tagline (English)'),
                                Forms\Components\TextInput::make('store_tagline_ar')
                                    ->label('Store Tagline (Arabic)')
                                    ->extraInputAttributes(['dir' => 'rtl']),
                            ]),

                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\FileUpload::make('store_logo')
                                    ->label('Store Logo')
                                    ->image()
                                    ->directory('settings'),
                                Forms\Components\FileUpload::make('store_favicon')
                                    ->label('Store Favicon')
                                    ->image()
                                    ->directory('settings'),
                            ]),

                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('store_email')
                                    ->label('Store Email')
                                    ->email(),
                                Forms\Components\TextInput::make('store_phone')
                                    ->label('Store Phone'),
                            ]),

                        Forms\Components\TextInput::make('store_whatsapp')
                            ->label('WhatsApp Number')
                            ->helperText('Used for the WhatsApp chat button on frontend'),

                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('store_address_en')
                                    ->label('Store Address (English)')
                                    ->rows(2),
                                Forms\Components\Textarea::make('store_address_ar')
                                    ->label('Store Address (Arabic)')
                                    ->rows(2)
                                    ->extraInputAttributes(['dir' => 'rtl']),
                            ]),
                    ]),

                // Regional Settings
                Components\Section::make('Regional Settings')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Select::make('default_language')
                                    ->label('Default Language')
                                    ->options(fn() => \App\Models\Language::where('is_active', true)->pluck('name', 'code')->toArray())
                                    ->default('en'),

                                Forms\Components\Select::make('default_currency')
                                    ->label('Default Currency')
                                    ->options(fn() => \App\Models\Currency::where('is_active', true)->pluck('name', 'code')->toArray())
                                    ->default('SAR'),

                                Forms\Components\Select::make('timezone')
                                    ->label('Timezone')
                                    ->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))
                                    ->searchable()
                                    ->default('Asia/Riyadh'),

                                Forms\Components\Select::make('date_format')
                                    ->label('Date Format')
                                    ->options([
                                        'DD/MM/YYYY' => 'DD/MM/YYYY',
                                        'MM/DD/YYYY' => 'MM/DD/YYYY',
                                        'YYYY-MM-DD' => 'YYYY-MM-DD',
                                    ])
                                    ->default('DD/MM/YYYY'),
                            ]),
                    ]),

                // Storefront
                Components\Section::make('Storefront')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('products_per_page')
                                    ->label('Products Per Page')
                                    ->numeric()
                                    ->default(12),

                                Forms\Components\TextInput::make('low_stock_threshold')
                                    ->label('Low Stock Threshold')
                                    ->numeric()
                                    ->default(5),
                            ]),

                        Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('enable_guest_checkout')
                                    ->label('Enable Guest Checkout')
                                    ->default(true),
                                Forms\Components\Toggle::make('enable_wishlist')
                                    ->label('Enable Wishlist')
                                    ->default(true),
                                Forms\Components\Toggle::make('enable_reviews')
                                    ->label('Enable Product Reviews')
                                    ->default(true),
                            ]),

                        Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('reviews_require_approval')
                                    ->label('Reviews Require Approval')
                                    ->default(true),
                                Forms\Components\Toggle::make('show_out_of_stock')
                                    ->label('Show Out of Stock Products')
                                    ->default(true),
                            ]),
                    ]),

                // Maintenance Mode
                Components\Section::make('Maintenance Mode')
                    ->schema([
                        Forms\Components\Toggle::make('maintenance_mode')
                            ->label('⚠ Enable Maintenance Mode')
                            ->helperText('Warning: Enabling this will show maintenance page to all storefront visitors.')
                            ->default(false),

                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('maintenance_message_en')
                                    ->label('Maintenance Message (English)')
                                    ->rows(2)
                                    ->default('We are currently performing maintenance. Please check back later.'),
                                Forms\Components\Textarea::make('maintenance_message_ar')
                                    ->label('Maintenance Message (Arabic)')
                                    ->rows(2)
                                    ->extraInputAttributes(['dir' => 'rtl']),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::set("general.{$key}", $value);
        }

        Notification::make()
            ->title('Settings Saved')
            ->body('All settings have been saved and cache cleared.')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('💾 Save Settings')
                ->submit('save'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('settings.view'));
    }
}

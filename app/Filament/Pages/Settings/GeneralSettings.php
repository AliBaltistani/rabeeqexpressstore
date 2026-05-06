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

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.settings_pages.general');
    }

    public function getTitle(): string
    {
        return __('admin.settings_pages.general');
    }

    public function mount(): void
    {
        $fields = [
            'general.store_name_en', 'general.store_name_ar', 'general.store_tagline_en', 'general.store_tagline_ar',
            'general.store_logo', 'general.store_favicon', 'general.store_email', 'general.store_phone',
            'general.store_whatsapp', 'general.store_address_en', 'general.store_address_ar',
            'general.timezone', 'general.date_format',
            'general.products_per_page', 'general.enable_guest_checkout', 'general.enable_wishlist',
            'general.enable_reviews', 'general.reviews_require_approval', 'general.show_out_of_stock',
            'general.low_stock_threshold',
            'general.maintenance_mode', 'general.maintenance_message_en', 'general.maintenance_message_ar',
        ];

        $this->data = [];
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
                Components\Section::make(__('admin.settings.store_info'))
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('store_name_en')
                                    ->label(__('admin.settings.store_name_en'))
                                    ->required(),
                                Forms\Components\TextInput::make('store_name_ar')
                                    ->label(__('admin.settings.store_name_ar'))
                                    ->required()
                                    ->extraInputAttributes(['dir' => 'rtl']),
                            ]),

                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('store_tagline_en')
                                    ->label(__('admin.settings.store_tagline_en')),
                                Forms\Components\TextInput::make('store_tagline_ar')
                                    ->label(__('admin.settings.store_tagline_ar'))
                                    ->extraInputAttributes(['dir' => 'rtl']),
                            ]),

                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\FileUpload::make('store_logo')
                                    ->label(__('admin.settings.store_logo'))
                                    ->image()
                                    ->directory('settings'),
                                Forms\Components\FileUpload::make('store_favicon')
                                    ->label(__('admin.settings.store_favicon'))
                                    ->image()
                                    ->directory('settings'),
                            ]),

                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('store_email')
                                    ->label(__('admin.settings.store_email'))
                                    ->email(),
                                Forms\Components\TextInput::make('store_phone')
                                    ->label(__('admin.settings.store_phone')),
                            ]),

                        Forms\Components\TextInput::make('store_whatsapp')
                            ->label(__('admin.settings.store_whatsapp')),

                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('store_address_en')
                                    ->label(__('admin.settings.store_address_en'))
                                    ->rows(2),
                                Forms\Components\Textarea::make('store_address_ar')
                                    ->label(__('admin.settings.store_address_ar'))
                                    ->rows(2)
                                    ->extraInputAttributes(['dir' => 'rtl']),
                            ]),
                    ]),

                // Regional Settings — Language & Currency managed in their own pages
                Components\Section::make(__('admin.settings.regional'))
                    ->description(__('admin.settings_pages.languages') . ' & ' . __('admin.settings_pages.currencies') . ' → ' . __('admin.nav.settings'))
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('timezone')
                                    ->label(__('admin.settings.timezone'))
                                    ->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))
                                    ->searchable()
                                    ->default('Asia/Riyadh'),

                                Forms\Components\Select::make('date_format')
                                    ->label(__('admin.settings.date_format'))
                                    ->options([
                                        'DD/MM/YYYY' => 'DD/MM/YYYY',
                                        'MM/DD/YYYY' => 'MM/DD/YYYY',
                                        'YYYY-MM-DD' => 'YYYY-MM-DD',
                                    ])
                                    ->default('DD/MM/YYYY'),
                            ]),
                    ]),

                // Storefront
                Components\Section::make(__('admin.settings.storefront'))
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('products_per_page')
                                    ->label(__('admin.settings.products_per_page'))
                                    ->numeric()
                                    ->default(12),

                                Forms\Components\TextInput::make('low_stock_threshold')
                                    ->label(__('admin.settings.low_stock_threshold'))
                                    ->numeric()
                                    ->default(5),
                            ]),

                        Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('enable_guest_checkout')
                                    ->label(__('admin.settings.enable_guest_checkout'))
                                    ->default(true),
                                Forms\Components\Toggle::make('enable_wishlist')
                                    ->label(__('admin.settings.enable_wishlist'))
                                    ->default(true),
                                Forms\Components\Toggle::make('enable_reviews')
                                    ->label(__('admin.settings.enable_reviews'))
                                    ->default(true),
                            ]),

                        Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('reviews_require_approval')
                                    ->label(__('admin.settings.reviews_require_approval'))
                                    ->default(true),
                                Forms\Components\Toggle::make('show_out_of_stock')
                                    ->label(__('admin.settings.show_out_of_stock'))
                                    ->default(true),
                            ]),
                    ]),

                // Maintenance Mode
                Components\Section::make(__('admin.settings.maintenance_mode'))
                    ->schema([
                        Forms\Components\Toggle::make('maintenance_mode')
                            ->label(__('admin.settings.maintenance_mode'))
                            ->helperText(__('admin.settings.maintenance_warning'))
                            ->default(false),

                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('maintenance_message_en')
                                    ->label(__('admin.settings.maintenance_message_en'))
                                    ->rows(2)
                                    ->default('We are currently performing maintenance. Please check back later.'),
                                Forms\Components\Textarea::make('maintenance_message_ar')
                                    ->label(__('admin.settings.maintenance_message_ar'))
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
            ->title(__('admin.settings.settings_saved'))
            ->body(__('admin.settings.settings_saved_body'))
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin.common.save_settings'))
                ->submit('save'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('settings.view'));
    }
}

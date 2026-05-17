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

class SocialLoginSettingsPage extends Page
{
    protected string $view = 'filament.pages.settings.general-settings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-share';

    protected static ?int $navigationSort = 5;

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.settings_pages.social_login');
    }

    public function getTitle(): string
    {
        return __('admin.settings_pages.social_login');
    }

    public function mount(): void
    {
        $fields = [
            'social.google_enabled', 'social.google_client_id', 'social.google_client_secret',
            'social.facebook_enabled', 'social.facebook_client_id', 'social.facebook_client_secret',
            'social.apple_enabled', 'social.apple_client_id', 'social.apple_client_secret',
            'social.apple_team_id', 'social.apple_key_id',
        ];

        $this->data = [];
        foreach ($fields as $field) {
            $key = str_replace('social.', '', $field);
            $this->data[$key] = Setting::get($field);
        }

        $this->form->fill($this->data);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                // Google
                Components\Section::make(__('admin.social.google'))
                    ->schema([
                        Forms\Components\Toggle::make('google_enabled')
                            ->label(__('admin.social.enable'))
                            ->default(false)
                            ->live(),

                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('google_client_id')
                                    ->label(__('admin.social.client_id'))
                                    ->password()
                                    ->revealable(),

                                Forms\Components\TextInput::make('google_client_secret')
                                    ->label(__('admin.social.client_secret'))
                                    ->password()
                                    ->revealable(),
                            ])
                            ->visible(fn(Components\Utilities\Get $get): bool => (bool) $get('google_enabled')),
                    ])
                    ->icon('heroicon-o-globe-alt')
                    ->collapsible(),

                // Facebook
                Components\Section::make(__('admin.social.facebook'))
                    ->schema([
                        Forms\Components\Toggle::make('facebook_enabled')
                            ->label(__('admin.social.enable'))
                            ->default(false)
                            ->live(),

                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('facebook_client_id')
                                    ->label(__('admin.social.client_id'))
                                    ->password()
                                    ->revealable(),

                                Forms\Components\TextInput::make('facebook_client_secret')
                                    ->label(__('admin.social.client_secret'))
                                    ->password()
                                    ->revealable(),
                            ])
                            ->visible(fn(Components\Utilities\Get $get): bool => (bool) $get('facebook_enabled')),
                    ])
                    ->icon('heroicon-o-chat-bubble-oval-left')
                    ->collapsible(),

                // Apple
                Components\Section::make(__('admin.social.apple'))
                    ->schema([
                        Forms\Components\Toggle::make('apple_enabled')
                            ->label(__('admin.social.enable'))
                            ->default(false)
                            ->live(),

                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('apple_client_id')
                                    ->label(__('admin.social.client_id'))
                                    ->password()
                                    ->revealable(),

                                Forms\Components\TextInput::make('apple_client_secret')
                                    ->label(__('admin.social.client_secret'))
                                    ->password()
                                    ->revealable(),
                            ])
                            ->visible(fn(Components\Utilities\Get $get): bool => (bool) $get('apple_enabled')),

                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('apple_team_id')
                                    ->label(__('admin.social.apple_team_id')),

                                Forms\Components\TextInput::make('apple_key_id')
                                    ->label(__('admin.social.apple_key_id')),
                            ])
                            ->visible(fn(Components\Utilities\Get $get): bool => (bool) $get('apple_enabled')),
                    ])
                    ->icon('heroicon-o-device-phone-mobile')
                    ->collapsible(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::set("social.{$key}", $value);
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

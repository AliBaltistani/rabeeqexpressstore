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

                        Forms\Components\Placeholder::make('google_guide')
                            ->label('')
                            ->content(function () {
                                $appUrl   = rtrim(config('app.url'), '/');
                                $frontUrl = rtrim(env('FRONTEND_URL', $appUrl), '/');
                                return new \Illuminate\Support\HtmlString('
                                    <div style="background:#f0f7ff;border:1px solid #c3dafe;border-radius:8px;padding:14px 16px;font-size:13px;line-height:1.7;color:#1e3a5f">
                                        <strong> How to get your Google Client ID &amp; Secret</strong><br>
                                        1. Go to <a href="https://console.cloud.google.com/apis/credentials" target="_blank" style="color:#4285F4;text-decoration:underline">Google Cloud Console → APIs &amp; Services → Credentials</a><br>
                                        2. Click <strong>Create Credentials → OAuth 2.0 Client ID</strong>, type: <strong>Web application</strong><br>
                                        3. Under <strong>Authorised JavaScript origins</strong>, add:<br>
                                        &nbsp;&nbsp;&nbsp;• <code style="background:#e8f0fe;padding:1px 6px;border-radius:4px">' . $frontUrl . '</code><br>
                                        4. Under <strong>Authorised redirect URIs</strong>, add:<br>
                                        &nbsp;&nbsp;&nbsp;• <code style="background:#e8f0fe;padding:1px 6px;border-radius:4px">' . $frontUrl . '</code><br>
                                        5. Copy the <strong>Client ID</strong> and <strong>Client Secret</strong> into the fields below.
                                    </div>
                                ');
                            })
                            ->visible(fn(Components\Utilities\Get $get): bool => (bool) $get('google_enabled')),

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

                        Forms\Components\Placeholder::make('facebook_guide')
                            ->label('')
                            ->content(function () {
                                $frontUrl = rtrim(env('FRONTEND_URL', config('app.url')), '/');
                                return new \Illuminate\Support\HtmlString('
                                    <div style="background:#f0f4ff;border:1px solid #bfcfff;border-radius:8px;padding:14px 16px;font-size:13px;line-height:1.7;color:#1a1f6e">
                                        <strong>How to get your Facebook App ID &amp; Secret</strong><br>
                                        1. Go to <a href="https://developers.facebook.com/apps" target="_blank" style="color:#1877F2;text-decoration:underline">Meta for Developers → My Apps</a><br>
                                        2. Create an app (type: <strong>Consumer</strong> or <strong>None</strong>), then go to <strong>Settings → Basic</strong><br>
                                        3. Under <strong>App Domains</strong>, add: <code style="background:#dde6ff;padding:1px 6px;border-radius:4px">' . parse_url($frontUrl, PHP_URL_HOST) . '</code><br>
                                        4. Go to <strong>Facebook Login → Settings</strong>, add under <strong>Valid OAuth Redirect URIs</strong>:<br>
                                        &nbsp;&nbsp;&nbsp;• <code style="background:#dde6ff;padding:1px 6px;border-radius:4px">' . $frontUrl . '</code><br>
                                        5. Copy <strong>App ID</strong> (= Client ID) and <strong>App Secret</strong> (= Client Secret) into the fields below.
                                    </div>
                                ');
                            })
                            ->visible(fn(Components\Utilities\Get $get): bool => (bool) $get('facebook_enabled')),

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

                        Forms\Components\Placeholder::make('apple_guide')
                            ->label('')
                            ->content(function () {
                                $frontUrl = rtrim(env('FRONTEND_URL', config('app.url')), '/');
                                $domain   = parse_url($frontUrl, PHP_URL_HOST);
                                return new \Illuminate\Support\HtmlString('
                                    <div style="background:#f5f5f7;border:1px solid #d1d1d6;border-radius:8px;padding:14px 16px;font-size:13px;line-height:1.7;color:#1c1c1e">
                                        <strong>How to get your Apple Service ID &amp; Secret</strong><br>
                                        1. Go to <a href="https://developer.apple.com/account/resources/identifiers/list/serviceId" target="_blank" style="color:#555;text-decoration:underline">Apple Developer → Identifiers → Services IDs</a><br>
                                        2. Create a <strong>Services ID</strong> — this is your <strong>Client ID</strong> (e.g. <em>com.yourapp.web</em>)<br>
                                        3. Enable <strong>Sign In with Apple</strong> and click Configure:<br>
                                        &nbsp;&nbsp;&nbsp;• <strong>Domains:</strong> <code style="background:#e5e5ea;padding:1px 6px;border-radius:4px">' . $domain . '</code><br>
                                        &nbsp;&nbsp;&nbsp;• <strong>Return URL:</strong> <code style="background:#e5e5ea;padding:1px 6px;border-radius:4px">' . $frontUrl . '</code><br>
                                        4. Generate a <strong>Private Key</strong> (type: Sign In with Apple) → use the <strong>Key ID</strong> and <strong>Team ID</strong> below<br>
                                        5. The <strong>Client Secret</strong> must be a JWT signed with your private key (<a href="https://developer.apple.com/documentation/accountorganizationaldatasharing/creating-a-client-secret" target="_blank" style="color:#555;text-decoration:underline">Apple docs</a>)
                                    </div>
                                ');
                            })
                            ->visible(fn(Components\Utilities\Get $get): bool => (bool) $get('apple_enabled')),

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

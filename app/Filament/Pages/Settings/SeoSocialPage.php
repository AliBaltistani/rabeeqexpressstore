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

class SeoSocialPage extends Page
{
    protected string $view = 'filament.pages.settings.general-settings';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?int $navigationSort = 7;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.settings_pages.seo');
    }

    public function getTitle(): string
    {
        return __('admin.settings_pages.seo');
    }

    public ?array $data = [];

    public function mount(): void
    {
        $fields = [
            'site_title_en', 'site_title_ar', 'meta_description_en', 'meta_description_ar', 'meta_keywords',
            'og_site_name', 'og_image', 'twitter_card', 'twitter_handle',
            'ga_id', 'gtm_id', 'fb_pixel_id', 'snap_pixel_id', 'tiktok_pixel_id',
            'instagram_url', 'snapchat_url', 'tiktok_url', 'twitter_url', 'facebook_url', 'youtube_url',
        ];
        foreach ($fields as $key) {
            $this->data[$key] = Setting::get("seo.{$key}");
        }
        $this->form->fill($this->data);
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Components\Section::make('Default SEO')->schema([
                Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('site_title_en')->label('Site Title (EN)'),
                    Forms\Components\TextInput::make('site_title_ar')->label('Site Title (AR)')->extraInputAttributes(['dir' => 'rtl']),
                ]),
                Components\Grid::make(2)->schema([
                    Forms\Components\Textarea::make('meta_description_en')->label('Meta Description (EN)')->rows(2)->maxLength(160),
                    Forms\Components\Textarea::make('meta_description_ar')->label('Meta Description (AR)')->rows(2)->maxLength(160)->extraInputAttributes(['dir' => 'rtl']),
                ]),
                Forms\Components\TextInput::make('meta_keywords')->label('Default Meta Keywords'),
            ]),
            Components\Section::make('Open Graph')->schema([
                Forms\Components\TextInput::make('og_site_name')->label('OG Site Name'),
                Forms\Components\FileUpload::make('og_image')->label('Default OG Image')->image()->directory('settings')->helperText('1200×630px recommended'),
                Components\Grid::make(2)->schema([
                    Forms\Components\Select::make('twitter_card')->options(['summary' => 'Summary', 'summary_large_image' => 'Summary Large Image'])->default('summary_large_image'),
                    Forms\Components\TextInput::make('twitter_handle')->label('Twitter Handle')->placeholder('@username'),
                ]),
            ]),
            Components\Section::make('Analytics & Tracking')->schema([
                Components\Grid::make(3)->schema([
                    Forms\Components\TextInput::make('ga_id')->label('Google Analytics ID')->placeholder('G-XXXXXXXXXX'),
                    Forms\Components\TextInput::make('gtm_id')->label('GTM ID')->placeholder('GTM-XXXXXX'),
                    Forms\Components\TextInput::make('fb_pixel_id')->label('Facebook Pixel ID'),
                ]),
                Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('snap_pixel_id')->label('Snapchat Pixel ID'),
                    Forms\Components\TextInput::make('tiktok_pixel_id')->label('TikTok Pixel ID'),
                ]),
            ]),
            Components\Section::make('Social Media Links')->schema([
                Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('instagram_url')->label('Instagram')->url()->placeholder('https://'),
                    Forms\Components\TextInput::make('snapchat_url')->label('Snapchat')->url()->placeholder('https://'),
                    Forms\Components\TextInput::make('tiktok_url')->label('TikTok')->url()->placeholder('https://'),
                    Forms\Components\TextInput::make('twitter_url')->label('Twitter / X')->url()->placeholder('https://'),
                    Forms\Components\TextInput::make('facebook_url')->label('Facebook')->url()->placeholder('https://'),
                    Forms\Components\TextInput::make('youtube_url')->label('YouTube')->url()->placeholder('https://'),
                ]),
            ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        foreach ($data as $key => $value) {
            Setting::set("seo.{$key}", $value);
        }
        Notification::make()->title('SEO & Social Settings Saved')->success()->send();
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('settings.view'));
    }
}

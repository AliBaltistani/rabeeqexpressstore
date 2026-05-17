<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetLanguageAndRtl;
use App\Models\Setting;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Dynamic brand name from settings (locale-aware)
        $brandName = $this->getBrandName();

        $panelConfig = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration(false)
            ->authGuard('admin')
            ->brandName($brandName)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                __('admin.nav.catalog'),
                __('admin.nav.sales'),
                __('admin.nav.content'),
                __('admin.nav.promotions'),
                __('admin.nav.settings'),
                __('admin.nav.system'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
            ])
            ->renderHook(
                'panels::user-menu.before',
                fn (): string => \Livewire\Livewire::mount('language-switcher'),
            )
            ->renderHook(
                'panels::head.end',
                function (): string {
                    $isRtl = session('is_rtl', false);
                    $dir = $isRtl ? 'rtl' : 'ltr';
                    return "<script>document.documentElement.dir = '{$dir}';document.documentElement.lang = '" . app()->getLocale() . "';</script>";
                },
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLanguageAndRtl::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        // Dynamic favicon from settings
        $favicon = $this->getFavicon();
        if ($favicon) {
            $panelConfig->favicon($favicon);
        }

        return $panelConfig;
    }

    /**
     * Get the store name from settings for the panel brand.
     * Returns locale-aware name.
     */
    protected function getBrandName(): string
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $locale = app()->getLocale();
                if ($locale === 'ar') {
                    $name = Setting::get('general.store_name_ar');
                    if ($name) return $name;
                }
                return Setting::get('general.store_name_en', 'Eseven Store') ?? 'Eseven Store';
            }
        } catch (\Throwable) {
            // Silently fail during migrations
        }

        return 'Eseven Store';
    }

    /**
     * Get the favicon URL from settings.
     */
    protected function getFavicon(): ?string
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $favicon = Setting::get('general.store_favicon');
                if ($favicon) {
                    return asset('storage/' . $favicon);
                }
            }
        } catch (\Throwable) {
            // Silently fail during migrations
        }

        return null;
    }
}

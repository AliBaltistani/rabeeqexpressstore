<?php

namespace App\Providers;

use App\Models\Setting;
use App\Policies\ExportPolicy;
use App\Policies\ImportPolicy;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute($request->user() ? 120 : 60)->by($request->user()?->id ?: $request->ip());
        });

        $this->bootstrapSettingsFromDatabase();

        \App\Models\Order::observe(\App\Observers\OrderObserver::class);
        \App\Models\Review::observe(\App\Observers\ReviewObserver::class);

        // Allow admin users to download Filament import/export files.
        // Without this, Filament falls back to a strict user->is() identity
        // check on the imports/exports morph which fails with a 403.
        Gate::policy(Import::class, ImportPolicy::class);
        Gate::policy(Export::class, ExportPolicy::class);
    }

    /**
     * Apply admin-panel settings to Laravel's runtime config.
     *
     * Runs only after migrations have created the settings table, and
     * wraps the whole block in a try/catch so a missing table never
     * crashes the app (e.g. during `php artisan migrate` on a fresh DB).
     */
    protected function bootstrapSettingsFromDatabase(): void
    {
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }

            // ── General / Regional ──────────────────────────────────
            $timezone = Setting::get('general.timezone');
            if ($timezone) {
                Config::set('app.timezone', $timezone);
                date_default_timezone_set($timezone);
            }

            $locale = Setting::get('general.default_language');
            if ($locale) {
                Config::set('app.locale', $locale);
                app()->setLocale($locale);
            }

            $storeName = Setting::get('general.store_name_en');
            if ($storeName) {
                Config::set('app.name', $storeName);
            }

            // ── Mail / SMTP ─────────────────────────────────────────
            $mailer = Setting::get('email.mailer');
            if ($mailer) {
                Config::set('mail.default', $mailer);
            }

            $smtpHost = Setting::get('email.smtp_host');
            if ($smtpHost) {
                Config::set('mail.mailers.smtp.host', $smtpHost);
            }

            $smtpPort = Setting::get('email.smtp_port');
            if ($smtpPort) {
                Config::set('mail.mailers.smtp.port', (int) $smtpPort);
            }

            $smtpUser = Setting::get('email.smtp_username');
            if ($smtpUser) {
                Config::set('mail.mailers.smtp.username', $smtpUser);
            }

            $smtpPass = Setting::get('email.smtp_password');
            if ($smtpPass) {
                Config::set('mail.mailers.smtp.password', $smtpPass);
            }

            $smtpEncryption = Setting::get('email.smtp_encryption');
            if ($smtpEncryption) {
                Config::set('mail.mailers.smtp.encryption', $smtpEncryption);
                // For Laravel 9+ Symfony Mailer compatibility:
                $scheme = $smtpEncryption === 'tls' ? null : $smtpEncryption; // tls uses null scheme and defaults to STARTTLS
                Config::set('mail.mailers.smtp.scheme', $scheme);
            }

            $fromEmail = Setting::get('email.from_email');
            if ($fromEmail) {
                Config::set('mail.from.address', $fromEmail);
            }

            $fromName = Setting::get('email.from_name');
            if ($fromName) {
                Config::set('mail.from.name', $fromName);
            }

        } catch (\Throwable $e) {
            // Silently fail during migrations or when DB is unreachable
            report($e);
        }
    }
}

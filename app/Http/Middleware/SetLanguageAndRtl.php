<?php

namespace App\Http\Middleware;

use App\Models\Language;
use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLanguageAndRtl
{
    /**
     * Handle an incoming request.
     *
     * Resolution order:
     *  1. Session value (set by LanguageSwitcher component)
     *  2. Admin's language_preference column (persisted per-admin)
     *  3. Default language from the languages table (is_default = true)
     *  4. Fallback to 'en'
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Determine the language code to use
        $language = $this->resolveLanguage($request);

        // Set app locale
        App::setLocale($language);
        Session::put('language', $language);

        // Determine RTL direction from the languages table
        $isRtl = $this->isRtlLanguage($language);
        Session::put('is_rtl', $isRtl);

        // Set HTML dir attribute on the request
        $request->attributes->set('dir', $isRtl ? 'rtl' : 'ltr');

        // Share direction with all views for Blade templates
        view()->share('dir', $isRtl ? 'rtl' : 'ltr');
        view()->share('isRtl', $isRtl);
        view()->share('currentLocale', $language);

        return $next($request);
    }

    /**
     * Resolve the active language code.
     */
    protected function resolveLanguage(Request $request): string
    {
        // 1. Check session first (set by the LanguageSwitcher dropdown)
        if (Session::has('language')) {
            $sessionLang = Session::get('language');
            if ($sessionLang && $this->isValidLanguage($sessionLang)) {
                return $sessionLang;
            }
        }

        // 2. Check authenticated admin's preference
        $admin = $request->user('admin');
        if ($admin && !empty($admin->language_preference)) {
            return $admin->language_preference;
        }

        // 3. Get default language from languages table
        try {
            if (Schema::hasTable('languages')) {
                $defaultLang = Language::where('is_default', true)
                    ->where('is_active', true)
                    ->first();
                if ($defaultLang) {
                    return $defaultLang->code;
                }
            }
        } catch (\Throwable) {
            // DB not ready
        }

        // 4. Fallback
        return config('app.locale', 'en');
    }

    /**
     * Check if a language code is valid and active.
     */
    protected function isValidLanguage(string $code): bool
    {
        try {
            if (Schema::hasTable('languages')) {
                return Language::where('code', $code)
                    ->where('is_active', true)
                    ->exists();
            }
        } catch (\Throwable) {
            // Ignore
        }

        return in_array($code, ['en', 'ar']);
    }

    /**
     * Check if the given language code uses RTL direction.
     */
    protected function isRtlLanguage(string $code): bool
    {
        // Well-known RTL codes
        $knownRtl = ['ar', 'he', 'fa', 'ur'];

        try {
            if (Schema::hasTable('languages')) {
                $lang = Language::where('code', $code)->first();
                if ($lang) {
                    return strtoupper($lang->direction) === 'RTL';
                }
            }
        } catch (\Throwable) {
            // Ignore
        }

        return in_array($code, $knownRtl);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetApiLocale
{
    /**
     * Resolve the locale for API requests.
     *
     * Priority: ?lang= query param → Accept-Language header → user preference → default setting → 'en'
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        App::setLocale($locale);

        return $next($request);
    }

    protected function resolveLocale(Request $request): string
    {
        $allowed = ['en', 'ar'];

        // 1. Query param
        $lang = $request->query('lang');
        if ($lang && in_array($lang, $allowed)) {
            return $lang;
        }

        // 2. Accept-Language header
        $acceptLang = $request->header('Accept-Language');
        if ($acceptLang) {
            $parsed = substr($acceptLang, 0, 2);
            if (in_array($parsed, $allowed)) {
                return $parsed;
            }
        }

        // 3. Authenticated user preference
        $user = $request->user();
        if ($user && !empty($user->language_preference) && in_array($user->language_preference, $allowed)) {
            return $user->language_preference;
        }

        // 4. Default from settings
        $default = setting('general.default_language', 'en');
        if (in_array($default, $allowed)) {
            return $default;
        }

        return 'en';
    }
}

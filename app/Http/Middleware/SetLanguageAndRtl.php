<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLanguageAndRtl
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Set language and RTL preference for authenticated users
        if ($request->user()) {
            $language = $request->user()->language_preference ?? 'en';
            $isRtl = $request->user()->is_rtl ?? false;
            
            // Set app locale
            App::setLocale($language);
            
            // Store RTL preference in session
            Session::put('is_rtl', $isRtl);
            Session::put('language', $language);
            
            // Set HTML dir attribute based on RTL preference
            $request->attributes->set('dir', $isRtl ? 'rtl' : 'ltr');
        }
        
        return $next($request);
    }
}

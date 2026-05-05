<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure admin authentication for Filament admin panel
 * Explicitly uses the 'admin' guard for authentication
 */
class EnsureAdminAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated with admin guard
        if (!auth()->guard('admin')->check()) {
            return redirect()->route('filament.admin.auth.login');
        }

        // Check if admin is active
        if (auth()->guard('admin')->user() && !auth()->guard('admin')->user()->is_active) {
            auth()->guard('admin')->logout();
            $request->session()->invalidate();
            
            return redirect()->route('filament.admin.auth.login')
                ->withErrors(['email' => 'Your admin account has been deactivated.']);
        }

        return $next($request);
    }
}

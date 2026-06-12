<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminTwoFactorMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('super_admin');

        if ($guard->check()) {
            $user = $guard->user();

            if (! empty($user->two_factor_secret)) {
                // If not verified yet, redirect to 2FA challenge page
                if (! session()->get('super_admin_2fa_verified')) {
                    // Let them access the challenge page or log out
                    if ($request->routeIs('filament.superadmin.pages.two-factor-challenge') ||
                        $request->routeIs('filament.superadmin.auth.logout')) {
                        return $next($request);
                    }

                    return redirect()->route('filament.superadmin.pages.two-factor-challenge');
                }
            }
        }

        return $next($request);
    }
}

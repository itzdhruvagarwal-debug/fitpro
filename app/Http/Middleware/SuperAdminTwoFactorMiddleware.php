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

            // 1. If 2FA is not set up, they MUST set it up on the TwoFactorSettings page.
            if (empty($user->two_factor_secret)) {
                if ($request->routeIs('filament.superadmin.pages.two-factor-settings') ||
                    $request->routeIs('filament.superadmin.auth.logout')) {
                    return $next($request);
                }

                return redirect()->route('filament.superadmin.pages.two-factor-settings');
            }

            // 2. If 2FA is set up but not verified in the session, they MUST verify it on the challenge page.
            if (! session()->get('super_admin_2fa_verified')) {
                if ($request->routeIs('filament.superadmin.pages.two-factor-challenge') ||
                    $request->routeIs('filament.superadmin.auth.logout')) {
                    return $next($request);
                }

                return redirect()->route('filament.superadmin.pages.two-factor-challenge');
            }
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\Gym;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Gym|null $tenant */
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;

        if (! $tenant) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tenant not found.',
                ], 404);
            }

            return redirect()->away('https://'.config('app.base_domain'));
        }

        if ($tenant->status === 'suspended') {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tenant account is suspended.',
                ], 403);
            }

            return response()->view('errors.gym-suspended', ['tenant' => $tenant], 403);
        }

        if (! $tenant->isOnTrial() && ! $tenant->isActive()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tenant subscription expired.',
                ], 402);
            }

            return response()->view('errors.subscription-expired', ['tenant' => $tenant], 402);
        }

        return $next($request);
    }
}

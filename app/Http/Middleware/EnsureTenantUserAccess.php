<?php

namespace App\Http\Middleware;

use App\Models\Gym;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure the authenticated user belongs to the active tenant and is active.
 */
class EnsureTenantUserAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return $next($request);
        }

        /** @var Gym|null $tenant */
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;
        abort_unless($tenant instanceof Gym, 403, 'Tenant context is required.');
        abort_unless((int) ($user->gym_id ?? 0) === (int) $tenant->id, 403);

        $status = (string) ($user->status?->value ?? $user->status ?? '');
        abort_if($status !== 'active', 403, 'Your account is inactive.');

        $token = $user->currentAccessToken();
        $expiresAt = $token?->expires_at ?? null;

        if (is_object($expiresAt) && method_exists($expiresAt, 'isPast') && $expiresAt->isPast()) {
            $token->delete();

            return response()->json([
                'message' => 'Token expired.',
            ], 401);
        }

        return $next($request);
    }
}

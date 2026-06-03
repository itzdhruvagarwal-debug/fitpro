<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\Gym;
use App\Models\User;
use App\Support\Data;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Authentication endpoints for API v1 (Sanctum bearer tokens).
 */
class AuthController extends ApiController
{
    /**
     * Create a Sanctum bearer token for a user.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;
        abort_unless($tenant instanceof Gym, 403, 'Tenant context is required.');
        $email = mb_strtolower(trim($request->string('email')->toString()));

        $user = User::query()
            ->where('gym_id', (int) $tenant->id)
            ->where('email', $email)
            ->first();

        if (! $user || ! Hash::check(Data::string($request->input('password')), Data::string($user->password))) {
            $this->writeSecurityAuditLog(
                eventType: 'auth.login',
                outcome: 'failed',
                request: $request,
                gymId: (int) $tenant->id,
                email: $email,
                context: ['reason' => 'invalid_credentials'],
            );

            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if ((int) ($user->gym_id ?? 0) !== (int) $tenant->id) {
            $this->writeSecurityAuditLog(
                eventType: 'auth.login',
                outcome: 'blocked',
                request: $request,
                gymId: (int) $tenant->id,
                userId: (int) $user->id,
                email: $email,
                context: ['reason' => 'tenant_mismatch'],
            );

            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if ((string) ($user->status?->value ?? $user->status ?? '') !== 'active') {
            $this->writeSecurityAuditLog(
                eventType: 'auth.login',
                outcome: 'blocked',
                request: $request,
                gymId: (int) $tenant->id,
                userId: (int) $user->id,
                email: $email,
                context: ['reason' => 'inactive_user'],
            );

            throw ValidationException::withMessages([
                'email' => ['Your account is inactive. Please contact your admin.'],
            ]);
        }

        $deviceName = Data::string($request->input('device_name')) ?: Data::string($request->userAgent(), 'api');
        $deviceName = mb_substr($deviceName, 0, 255);
        $tokenExpiry = now()->addDays(30);

        $user->tokens()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();

        $token = $user->createToken($deviceName, ['*'], $tokenExpiry)->plainTextToken;
        $this->writeSecurityAuditLog(
            eventType: 'auth.login',
            outcome: 'success',
            request: $request,
            gymId: (int) $tenant->id,
            userId: (int) $user->id,
            email: $email,
            context: ['device_name' => $deviceName],
        );

        $user->load('roles');

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Return the authenticated user (includes roles and permissions).
     */
    public function me(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();
        $this->ensureTenantUserAccess($user);

        $user->load('roles');

        return new UserResource($user);
    }

    /**
     * Revoke the current token.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->ensureTenantUserAccess($user);
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;

        if ($request->bearerToken() !== null && $user->currentAccessToken() !== null) {
            $user->currentAccessToken()->delete();
        } else {
            $user->tokens()->delete();
        }

        $this->writeSecurityAuditLog(
            eventType: 'auth.logout',
            outcome: 'success',
            request: $request,
            gymId: $tenant instanceof Gym ? (int) $tenant->id : null,
            userId: (int) $user->id,
            email: (string) $user->email,
        );

        return $this->noContent();
    }

    private function ensureTenantUserAccess(User $user): void
    {
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;
        abort_unless($tenant instanceof Gym, 403, 'Tenant context is required.');
        abort_unless((int) $user->gym_id === (int) $tenant->id, 403);
    }

    /**
     * @param  array<string, mixed>|null  $context
     */
    private function writeSecurityAuditLog(
        string $eventType,
        string $outcome,
        Request $request,
        ?int $gymId = null,
        ?int $userId = null,
        ?string $email = null,
        ?array $context = null,
    ): void {
        try {
            DB::table('security_audit_logs')->insert([
                'gym_id' => $gymId,
                'user_id' => $userId,
                'event_type' => $eventType,
                'email' => $email,
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'outcome' => $outcome,
                'context' => $context !== null ? json_encode($context, JSON_THROW_ON_ERROR) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            // Logging failures must never break auth flow.
        }
    }
}

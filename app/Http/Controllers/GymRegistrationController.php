<?php

namespace App\Http\Controllers;

use App\Models\Gym;
use App\Models\Plan;
use App\Models\Service;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class GymRegistrationController extends Controller
{
    public function showRegistrationForm(): View
    {
        return view('gym-registration');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'gym_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $data['email'] = Str::lower(trim((string) $data['email']));

        $gym = $this->createGymWithRetry($data);
        $subdomainUrl = 'https://'.$gym->slug.'.'.config('app.base_domain').'/admin';

        return redirect()->away($subdomainUrl);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createGymWithRetry(array $data): Gym
    {
        $slugBase = Str::slug($data['gym_name']);
        if ($slugBase === '') {
            $slugBase = 'gym';
        }

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $slug = $this->nextAvailableSlug($slugBase);

            try {
                return DB::transaction(fn (): Gym => $this->createGymTenant($data, $slug));
            } catch (UniqueConstraintViolationException $exception) {
                if (! $this->isSlugCollision($exception)) {
                    throw $exception;
                }
            }
        }

        throw ValidationException::withMessages([
            'gym_name' => 'Could not reserve a unique gym URL. Please try again.',
        ]);
    }

    private function nextAvailableSlug(string $slugBase): string
    {
        $slug = $slugBase;
        $i = 1;

        while (Gym::query()->where('slug', $slug)->exists()) {
            $i++;
            $slug = $slugBase.'-'.$i;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createGymTenant(array $data, string $slug): Gym
    {
        $gym = Gym::query()->create([
            'name' => $data['gym_name'],
            'slug' => $slug,
            'owner_name' => $data['owner_name'],
            'owner_email' => $data['email'],
            'owner_phone' => $data['phone'],
            'city' => $data['city'] ?? null,
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
            'database' => 'default',
        ]);

        TenantContext::run($gym, function () use ($data): void {
            $owner = User::query()->create([
                'name' => $data['owner_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'contact' => $data['phone'],
                'status' => 'active',
            ]);

            $ownerRoleName = (string) config('filament-shield.super_admin.name', 'super_admin');
            $ownerRole = Role::findOrCreate($ownerRoleName, 'web');
            $owner->assignRole($ownerRole);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $service = Service::query()->create([
                'name' => 'Gym Membership',
                'description' => 'Default membership service',
            ]);

            Plan::query()->create([
                'service_id' => $service->id,
                'name' => 'Monthly',
                'code' => 'MONTHLY',
                'amount' => 499,
                'days' => 30,
                'status' => 'active',
            ]);
            Plan::query()->create([
                'service_id' => $service->id,
                'name' => 'Quarterly',
                'code' => 'QUARTERLY',
                'amount' => 1299,
                'days' => 90,
                'status' => 'active',
            ]);
            Plan::query()->create([
                'service_id' => $service->id,
                'name' => 'Yearly',
                'code' => 'YEARLY',
                'amount' => 4999,
                'days' => 365,
                'status' => 'active',
            ]);
        });

        return $gym;
    }

    private function isSlugCollision(UniqueConstraintViolationException $exception): bool
    {
        $message = mb_strtolower($exception->getMessage());

        return str_contains($message, 'gyms_slug_unique')
            || (str_contains($message, 'gyms') && str_contains($message, 'slug'));
    }
}

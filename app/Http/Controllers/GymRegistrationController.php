<?php

namespace App\Http\Controllers;

use App\Models\Gym;
use App\Models\Plan;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GymRegistrationController extends Controller
{
    public function showRegistrationForm()
    {
        return view('gym-registration');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'gym_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $data['email'] = Str::lower(trim((string) $data['email']));

        $slugBase = Str::slug($data['gym_name']);
        if ($slugBase === '') {
            $slugBase = 'gym';
        }
        $slug = $slugBase;
        $i = 1;
        while (Gym::query()->where('slug', $slug)->exists()) {
            $i++;
            $slug = $slugBase.'-'.$i;
        }

        $gym = DB::transaction(function () use ($data, $slug): Gym {
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

            // Create gym admin user (tenant-scoped by gym_id).
            User::query()->create([
                'gym_id' => $gym->id,
                'name' => $data['owner_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'contact' => $data['phone'],
                'status' => 'active',
            ]);

            // Seed default services + plans for this gym.
            $service = Service::query()->create([
                'gym_id' => $gym->id,
                'name' => 'Gym Membership',
                'description' => 'Default membership service',
            ]);

            Plan::query()->create([
                'gym_id' => $gym->id,
                'service_id' => $service->id,
                'name' => 'Monthly',
                'code' => 'MONTHLY',
                'amount' => 499,
                'days' => 30,
                'status' => 'active',
            ]);
            Plan::query()->create([
                'gym_id' => $gym->id,
                'service_id' => $service->id,
                'name' => 'Quarterly',
                'code' => 'QUARTERLY',
                'amount' => 1299,
                'days' => 90,
                'status' => 'active',
            ]);
            Plan::query()->create([
                'gym_id' => $gym->id,
                'service_id' => $service->id,
                'name' => 'Yearly',
                'code' => 'YEARLY',
                'amount' => 4999,
                'days' => 365,
                'status' => 'active',
            ]);

            return $gym;
        });

        $subdomainUrl = 'https://'.$gym->slug.'.'.config('app.base_domain').'/admin';

        return redirect()->away($subdomainUrl);
    }
}

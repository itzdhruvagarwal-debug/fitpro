<?php

use App\Models\Gym;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Service;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

it('registers a gym with tenant-owned defaults and owner admin access', function (): void {
    $response = $this->post(route('gym.register'), [
        'gym_name' => 'Iron House',
        'owner_name' => 'Isha Owner',
        'email' => 'owner@ironhouse.test',
        'phone' => '919999999999',
        'city' => 'Agra',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $gym = Gym::query()->where('slug', 'iron-house')->first();

    expect($gym)->not->toBeNull();
    $response->assertRedirect('https://iron-house.'.config('app.base_domain').'/admin');

    $owner = User::query()
        ->withoutGlobalScopes()
        ->where('email', 'owner@ironhouse.test')
        ->first();

    expect($owner)->not->toBeNull()
        ->and((int) $owner?->gym_id)->toBe((int) $gym?->id);

    $gym?->makeCurrent();
    app(PermissionRegistrar::class)->setPermissionsTeamId($gym?->id);

    expect($owner?->hasRole('super_admin'))->toBeTrue()
        ->and(Service::query()->where('name', 'Gym Membership')->exists())->toBeTrue()
        ->and(Service::query()->where('name', 'Gym Membership')->first()?->gym_id)->toBe($gym?->id);
});

it('forces tenant-owned models onto the active gym even when gym_id is supplied', function (): void {
    $activeGym = app('currentTenant');
    $otherGym = Gym::query()->create([
        'slug' => 'other-gym',
        'name' => 'Other Gym',
        'status' => 'active',
        'database' => 'default',
    ]);

    $service = Service::query()->create([
        'gym_id' => $otherGym->id,
        'name' => 'Cross Tenant Attempt',
    ]);

    expect((int) $service->gym_id)->toBe((int) $activeGym->id);
});

it('rejects role ids that belong to another gym', function (): void {
    $currentGym = app('currentTenant');
    $otherGym = Gym::query()->create([
        'slug' => 'role-owner-gym',
        'name' => 'Role Owner Gym',
        'status' => 'active',
        'database' => 'default',
    ]);

    $otherRole = Role::query()->create([
        'name' => 'manager',
        'guard_name' => 'web',
        'gym_id' => $otherGym->id,
    ]);

    Permission::findOrCreate('Create:User', 'web');

    $actor = User::factory()->create([
        'gym_id' => $currentGym->id,
    ]);
    $actor->givePermissionTo('Create:User');

    Sanctum::actingAs($actor);

    $this->postJson('/api/v1/users', [
        'name' => 'Blocked Role User',
        'email' => 'blocked-role@example.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role_ids' => [$otherRole->id],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['role_ids.0']);
});

it('lists only global and current tenant roles through the api', function (): void {
    $currentGym = app('currentTenant');
    $otherGym = Gym::query()->create([
        'slug' => 'other-role-list-gym',
        'name' => 'Other Role List Gym',
        'status' => 'active',
        'database' => 'default',
    ]);

    $currentRole = Role::query()->create([
        'name' => 'front-desk',
        'guard_name' => 'web',
        'gym_id' => $currentGym->id,
    ]);
    $otherRole = Role::query()->create([
        'name' => 'other-front-desk',
        'guard_name' => 'web',
        'gym_id' => $otherGym->id,
    ]);

    Permission::findOrCreate('ViewAny:Role', 'web');

    $actor = User::factory()->create([
        'gym_id' => $currentGym->id,
    ]);
    $actor->givePermissionTo('ViewAny:Role');

    Sanctum::actingAs($actor);

    $roleIds = collect($this->getJson('/api/v1/roles')
        ->assertOk()
        ->json('data'))
        ->pluck('id')
        ->map(fn (mixed $id): int => (int) $id);

    expect($roleIds)->toContain((int) $currentRole->id)
        ->and($roleIds)->not->toContain((int) $otherRole->id);
});

it('rejects payment subscription plans from another gym before provider calls', function (): void {
    $currentGym = app('currentTenant');
    $otherGym = Gym::query()->create([
        'slug' => 'other-payment-plan-gym',
        'name' => 'Other Payment Plan Gym',
        'status' => 'active',
        'database' => 'default',
    ]);

    Permission::findOrCreate('Update:Member', 'web');

    $actor = User::factory()->create([
        'gym_id' => $currentGym->id,
    ]);
    $actor->givePermissionTo('Update:Member');

    $member = Member::factory()->create([
        'gym_id' => $currentGym->id,
    ]);

    $otherPlan = TenantContext::run($otherGym, fn (): Plan => Plan::factory()->create());

    $this->actingAs($actor)
        ->postJson(route('payment.subscribe', $member), [
            'plan_id' => $otherPlan->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['plan_id']);
});

it('marks overdue invoices only inside active tenant contexts', function (): void {
    $activeGym = app('currentTenant');
    $suspendedGym = Gym::query()->create([
        'slug' => 'suspended-invoice-gym',
        'name' => 'Suspended Invoice Gym',
        'status' => 'suspended',
        'database' => 'default',
    ]);

    $activeInvoice = Invoice::factory()->create([
        'due_date' => now()->addDay(),
        'status' => 'issued',
        'subscription_fee' => 100,
        'paid_amount' => 0,
    ]);
    Invoice::withoutEvents(fn () => $activeInvoice->forceFill([
        'due_date' => now()->subDay()->toDateString(),
        'status' => 'issued',
        'due_amount' => 100,
    ])->save());

    $suspendedInvoice = TenantContext::run($suspendedGym, function (): Invoice {
        $invoice = Invoice::factory()->create([
            'due_date' => now()->addDay(),
            'status' => 'issued',
            'subscription_fee' => 100,
            'paid_amount' => 0,
        ]);

        Invoice::withoutEvents(fn () => $invoice->forceFill([
            'due_date' => now()->subDay()->toDateString(),
            'status' => 'issued',
            'due_amount' => 100,
        ])->save());

        return $invoice;
    });

    $activeGym->makeCurrent();

    $this->artisan('gymie:invoices', ['--mark-overdue' => true])
        ->assertExitCode(0);

    expect($activeInvoice->refresh()->status->value)->toBe('overdue')
        ->and($suspendedInvoice->refresh()->status->value)->toBe('issued');
});

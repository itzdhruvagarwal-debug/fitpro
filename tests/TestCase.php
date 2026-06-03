<?php

namespace Tests;

use App\Models\Gym;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTenantContext();
    }

    protected function setUpTenantContext(): void
    {
        if (! Schema::hasTable('gyms')) {
            return;
        }

        $tenant = Gym::query()->firstOrCreate(
            ['slug' => 'test-gym'],
            [
                'name' => 'Test Gym',
                'owner_email' => 'owner@example.test',
                'status' => 'active',
                'plan_expires_at' => now()->addYear(),
                'trial_ends_at' => now()->addYear(),
                'database' => 'default',
            ],
        );

        $tenant->makeCurrent();

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());
    }
}

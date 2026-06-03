<?php

namespace Database\Seeders;

use App\Models\Gym;
use Illuminate\Database\Seeder;

class GymSeeder extends Seeder
{
    public function run(): void
    {
        $gym = Gym::query()->firstOrCreate(
            ['slug' => 'demo-gym'],
            [
                'name' => 'Demo Gym',
                'status' => 'active',
                'trial_ends_at' => now()->addDays(14),
                'database' => 'default',
            ],
        );

        // Seed all existing data under this tenant context.
        app()->instance('currentTenant', $gym);

        $this->call([
            ShieldSeeder::class,
            UserSeeder::class,
            ServiceSeeder::class,
            PlanSeeder::class,
            EnquirySeeder::class,
            FollowUpSeeder::class,
            MemberSeeder::class,
            SubscriptionSeeder::class,
            InvoiceSeeder::class,
            ExpenseSeeder::class,
        ]);

        app()->forgetInstance('currentTenant');
    }
}

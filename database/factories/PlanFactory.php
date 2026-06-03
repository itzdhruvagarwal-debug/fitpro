<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Service;
use Database\Factories\Concerns\ForCurrentTenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    use ForCurrentTenant;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gym_id' => $this->currentTenantId(),
            'service_id' => Service::query()->inRandomOrder()->value('id') ?? Service::factory(),
            'code' => $this->faker->unique()->numerify('PLN###'),
            'name' => $this->faker->name(),
            'description' => $this->faker->text(50),
            'days' => $this->faker->numberBetween(1, 365),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }
}

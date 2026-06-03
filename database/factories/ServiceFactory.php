<?php

namespace Database\Factories;

use App\Models\Service;
use Database\Factories\Concerns\ForCurrentTenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
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
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
        ];
    }
}

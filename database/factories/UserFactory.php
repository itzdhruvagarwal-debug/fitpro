<?php

namespace Database\Factories;

use App\Models\User;
use Database\Factories\Concerns\ForCurrentTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    use ForCurrentTenant;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public $status;

    public $gender;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $this->status = 'active';
        $this->gender = $this->faker->randomElement(['male', 'female', 'other']);

        return [
            'gym_id' => $this->currentTenantId(),
            'name' => $this->faker->company,
            'contact' => $this->faker->numerify('+##-##########'),
            'address' => $this->faker->address,
            'country' => $this->faker->country,
            'state' => $this->faker->state,
            'city' => $this->faker->city,
            'pincode' => $this->faker->randomNumber(6, 0),
            'gender' => $this->gender,
            'status' => $this->status,
            'dob' => $this->faker->date(),
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}

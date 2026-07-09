<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'            => $this->faker->name(),
            'national_id'     => $this->faker->unique()->numerify('#########'),
            'employee_number' => 'EMP-' . $this->faker->unique()->numerify('####'),
            'phone'           => $this->faker->phoneNumber(),
            'position'        => $this->faker->jobTitle(),
            'username'        => $this->faker->unique()->userName(),
            'password'        => 'password',
            'role'            => 'client',
            'is_active'       => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'admin']);
    }

    public function investor(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'investor']);
    }

    public function client(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'client']);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}

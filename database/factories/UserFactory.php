<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [ 
            'tenant_id'  => Tenant::factory(),
            'email'      => $this->faker->unique()->safeEmail,
            'password'   => bcrypt( 'password' ),
            'is_active'  => $this->faker->boolean,
            'logo'       => $this->faker->imageUrl,
        ];
    }

}

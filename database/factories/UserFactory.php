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
            'name'       => $this->faker->name,
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->unique()->safeEmail,
            'password'   => bcrypt( 'password' ),
            'phone'      => $this->faker->phoneNumber,
            'address'    => $this->faker->streetAddress,
            'city'       => $this->faker->city,
            'state'      => $this->faker->state,
            'zip_code'   => $this->faker->postcode,
            'is_active'  => $this->faker->boolean,
            'logo'       => $this->faker->imageUrl,
        ];
    }

}

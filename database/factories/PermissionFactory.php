<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [ 
            'name'       => $this->faker->randomElement( [ 'view-dashboard', 'create-budget', 'edit-service', 'delete-invoice', 'manage-users' ] ),
            'guard_name' => 'web',
        ];
    }

}

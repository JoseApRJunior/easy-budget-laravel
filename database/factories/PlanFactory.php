<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'        => $this->faker->company,
            'slug'        => $this->faker->unique()->slug( 2 ), // Slug mais curto para evitar problemas de tamanho
            'description' => $this->faker->sentence(),
            'price'       => $this->faker->randomFloat( 2, 0, 1000 ),
            'status'      => $this->faker->boolean( 90 ), // 90% de chance de estar ativo
            'max_budgets' => $this->faker->numberBetween( 10, 1000 ),
            'max_clients' => $this->faker->numberBetween( 1, 100 ),
            'features'    => json_encode( [
                'budgets'    => true,
                'clients'    => true,
                'reports'    => $this->faker->boolean( 80 ),
                'api_access' => $this->faker->boolean( 50 ),
            ] ),
        ];
    }

}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id'     => \App\Models\Tenant::factory(),
            'user_id'       => \App\Models\User::factory(),
            'hash'          => $this->faker->unique()->md5(),
            'type'          => $this->faker->randomElement( [ 'budget', 'customer', 'product', 'service' ] ),
            'description'   => $this->faker->sentence(),
            'file_name'     => $this->faker->word() . '.pdf',
            'file_path'     => null,
            'status'        => 'completed',
            'format'        => 'pdf',
            'size'          => $this->faker->randomFloat( 2, 1, 100 ),
            'filters'       => [ 'start_date' => now()->subMonth()->format( 'Y-m-d' ) ],
            'error_message' => null,
            'generated_at'  => now(),
        ];
    }

}

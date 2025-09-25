<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company . ' ' . $this->faker->randomElement( [ 'Ltda.', 'S/A', 'ME' ] );

        return [ 
            'name'       => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $tenant = Tenant::factory()->create();
        $slug   = $this->faker->unique()->slug( 2 );

        return [ 
            'tenant_id'   => $tenant->id,
            'name'        => $this->faker->word,
            'slug'        => $slug,
            'description' => $this->faker->sentence,
            'is_active'   => $this->faker->boolean,
        ];
    }

}

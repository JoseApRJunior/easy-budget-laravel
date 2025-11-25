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
        $slug = $this->faker->unique()->slug( 2 );

        return [
            'tenant_id'  => Tenant::factory(),
            'name'       => $this->faker->words(2, true),
            'slug'       => $slug,
            'description'=> $this->faker->sentence(),
            'is_active'  => true,
        ];
    }

}

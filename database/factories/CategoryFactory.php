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
        $slug = $this->faker->slug( 2 ); // Remover unique()

        return [
            'name'      => $this->faker->words( 2, true ),
            'slug'      => $slug,
            'is_active' => true,
        ];
    }

}

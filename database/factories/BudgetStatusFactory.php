<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BudgetStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetStatusFactory extends Factory
{
    protected $model = BudgetStatus::class;

    public function definition(): array
    {
        return [ 
            'name'        => $this->faker->word,
            'slug'        => $this->faker->unique()->slug,
            'description' => $this->faker->sentence,
            'color'       => $this->faker->hexColor,
            'icon'        => $this->faker->word . '-icon',
            'order_index' => $this->faker->numberBetween( 1, 10 ),
            'is_active'   => $this->faker->boolean,
        ];
    }

    /**
     * Estado para BudgetStatus 'pending' - usado em BudgetFactory.
     * Cria status inicial com slug e nome fixos, cor padrão e ordem 1.
     *
     * @return array<string, mixed>
     */
    public function pending(): static
    {
        return $this->state( fn( array $attributes ) => [ 
            'name'        => 'Pendente',
            'slug'        => 'pending',
            'description' => 'Status inicial do orçamento',
            'color'       => '#FFA500',
            'icon'        => 'pending-icon',
            'order_index' => 1,
            'is_active'   => true,
        ] );
    }

}

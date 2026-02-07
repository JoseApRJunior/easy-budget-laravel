<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerInteraction;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerInteractionFactory extends Factory
{
    protected $model = CustomerInteraction::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'customer_id' => Customer::factory(),
            'interaction_type' => $this->faker->randomElement([
                'call',
                'email',
                'meeting',
                'note',
                'follow_up',
            ]),
            'description' => $this->faker->optional()->sentence(),
            'outcome' => $this->faker->optional()->randomElement([
                'completed',
                'scheduled',
                'no_answer',
                'cancelled',
            ]),
            'next_action' => $this->faker->optional()->sentence(),
            'next_action_date' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'interaction_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'user_id' => $this->faker->optional()->numberBetween(1, 10),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'outcome' => 'completed',
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'outcome' => 'scheduled',
        ]);
    }
}

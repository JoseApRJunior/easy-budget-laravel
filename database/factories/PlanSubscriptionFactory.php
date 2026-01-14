<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Plan;
use App\Models\PlanSubscription;
use App\Models\Provider;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanSubscriptionFactory extends Factory
{
    protected $model = PlanSubscription::class;

    /**
     * Define o estado padrão da factory.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => 'active',
            'transaction_amount' => $this->faker->randomFloat(2, 50, 200),
            'start_date' => now(),
            'end_date' => null,
            'transaction_date' => null,
            'payment_method' => 'credit_card',
            'payment_id' => null,
            'public_hash' => null,
            'last_payment_date' => null,
            'next_payment_date' => now()->addMonth(),
        ];
    }

    /**
     * State for creating subscription for specific tenant and provider.
     */
    public function forTenantAndProvider(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $attributes['tenant_id'] ?? Tenant::factory()->create()->id,
            'provider_id' => $attributes['provider_id'] ?? Provider::factory()->create()->id,
            'plan_id' => $attributes['plan_id'] ?? Plan::factory()->create()->id,
        ]);
    }

    /**
     * State for cancelled subscription.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'end_date' => now(),
        ]);
    }
}
